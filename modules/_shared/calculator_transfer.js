(() => {
    const TI_VENDOR_ID = 0x0451;
    const EVO_PRODUCT_ID = 0xE018;
    // These two USB identities are shared by the entire TI-84 Plus family:
    // 84+/SE, 82 Advanced, 84+ T, CSE, CE/Premium CE, and 82AEP. tilibs
    // probes the DirectLink device after opening it to determine the model.
    const DIRECTLINK_PRODUCT_IDS = [0xE003, 0xE008];
    const TARGET_DIRECTLINK = 'directlink';
    const TARGET_EVO = 'evo';

    const state = {
        module: null,
        modulePromise: null,
        handle: 0,
        target: null,
        model: 0,
        modelName: '',
        device: null,
        resetNeeded: false,
        listenersBound: false
    };

    function requireSecureContext() {
        if (!self.isSecureContext) {
            throw new Error('Calculator transfer requires HTTPS or localhost.');
        }
    }

    function resetConnectionState() {
        state.handle = 0;
        state.target = null;
        state.model = 0;
        state.modelName = '';
        state.device = null;
        state.resetNeeded = true;
    }

    function bindDisconnectListeners() {
        if (state.listenersBound) {
            return;
        }
        navigator.usb?.addEventListener('disconnect', resetConnectionState);
        navigator.serial?.addEventListener('disconnect', resetConnectionState);
        state.listenersBound = true;
    }

    async function initModule() {
        if (state.modulePromise) {
            return state.modulePromise;
        }
        if (typeof PBWebTILPTransferModule === 'undefined') {
            throw new Error('Calculator transfer module not loaded.');
        }

        state.modulePromise = (async () => {
            const wasmUrl = window.pbWebTILPTransferWasmUrl;
            const module = await PBWebTILPTransferModule({
                locateFile: (path, prefix) => path === 'webtilp_transfer_module.wasm' && wasmUrl
                    ? wasmUrl
                    : prefix + path
            });
            const result = await module.ccall('pb_transfer_init', 'number', [], [], {async: true});
            if (result !== 0) {
                throw new Error(`Unable to initialize tilibs (error ${result}).`);
            }
            state.module = module;
            bindDisconnectListeners();
            return module;
        })();
        return state.modulePromise;
    }

    async function requestEvoSerialPort() {
        if (!navigator.serial) {
            throw new Error('The TI-83/84 Evo requires WebSerial (Chrome or Edge).');
        }
        return navigator.serial.requestPort({
            filters: [{usbVendorId: TI_VENDOR_ID, usbProductId: EVO_PRODUCT_ID}]
        });
    }

    async function authorizeCalculator(allowEvo) {
        requireSecureContext();

        if (navigator.usb) {
            const productIds = allowEvo ? [...DIRECTLINK_PRODUCT_IDS, EVO_PRODUCT_ID] : DIRECTLINK_PRODUCT_IDS;
            const usbDevice = await navigator.usb.requestDevice({
                filters: productIds.map(productId => ({vendorId: TI_VENDOR_ID, productId}))
            });
            if (usbDevice.productId === EVO_PRODUCT_ID) {
                return {
                    target: TARGET_EVO,
                    device: await requestEvoSerialPort()
                };
            }
            return {target: TARGET_DIRECTLINK, device: usbDevice};
        }

        if (allowEvo && navigator.serial) {
            return {target: TARGET_EVO, device: await requestEvoSerialPort()};
        }
        throw new Error('Calculator transfer requires WebUSB (or WebSerial for Evo).');
    }

    async function prepareTransfer(options = {}) {
        const allowEvo = options.allowEvo !== false;
        if (state.handle && state.target && (allowEvo || state.target === TARGET_DIRECTLINK)) {
            return {target: state.target, model: state.model, modelName: state.modelName};
        }

        // Keep the device picker directly in the button's user gesture. Loading
        // WASM first can consume the transient activation in some browsers.
        const selection = await authorizeCalculator(allowEvo);
        const module = await initModule();

        if (state.resetNeeded || state.handle) {
            await module.ccall('pb_transfer_disconnect', null, [], [], {async: true});
            state.resetNeeded = false;
            state.handle = 0;
        }

        if (selection.target === TARGET_EVO) {
            module.__ticablesWebSerial = {
                ...(module.__ticablesWebSerial || {}),
                kind: 1,
                port: selection.device
            };
        } else {
            delete module.__ticablesWebSerial;
        }

        const handle = await module.ccall('pb_transfer_create_handle', 'number', [], [], {async: true});
        if (!handle) {
            throw new Error('Unable to create the calculator connection.');
        }
        const openResult = await module.ccall(
            'pb_transfer_open_cable',
            'number',
            ['number'],
            [handle],
            {async: true}
        );
        if (openResult !== 0) {
            const detail = module.ccall('pb_transfer_error_message', 'string', ['number'], [openResult]);
            throw new Error(`Unable to open the calculator connection: ${detail}.`);
        }

        const probeResult = await module.ccall(
            'pb_transfer_probe_model',
            'number',
            ['number'],
            [handle],
            {async: true}
        );
        if (probeResult !== 0) {
            const detail = module.ccall('pb_transfer_error_message', 'string', ['number'], [probeResult]);
            throw new Error(`Unable to identify the connected calculator: ${detail}.`);
        }

        state.handle = handle;
        state.target = selection.target;
        state.model = module.ccall('pb_transfer_get_calc_model', 'number', [], []);
        state.modelName = module.ccall('pb_transfer_get_calc_model_name', 'string', [], []);
        state.device = selection.device;
        return {target: state.target, model: state.model, modelName: state.modelName};
    }

    async function sendFileBytes(fileBytes, filename, options = {}) {
        if (!state.handle) {
            await prepareTransfer(options);
        }
        const module = await initModule();
        const path = `/uploads/${String(filename).replace(/[\\/]/g, '_')}`;
        if (!module.FS.analyzePath('/uploads').exists) {
            module.FS.mkdir('/uploads');
        }
        module.FS.writeFile(path, fileBytes instanceof Uint8Array ? fileBytes : new Uint8Array(fileBytes));

        try {
            const result = await module.ccall(
                'pb_transfer_send_file',
                'number',
                ['number', 'string'],
                [state.handle, path],
                {async: true}
            );
            return {
                result,
                error: result === 0
                    ? ''
                    : module.ccall('pb_transfer_error_message', 'string', ['number'], [result])
            };
        } finally {
            try {
                module.FS.unlink(path);
            } catch (cleanupError) {
                console.warn('[Project Builder] Unable to remove transfer staging file', cleanupError);
            }
        }
    }

    window.pbCalculatorTransfer = {
        prepareTransfer,
        sendFileBytes,
        // Keep `ce` as a compatibility alias for callers predating the
        // DirectLink-wide model probe.
        targets: {directlink: TARGET_DIRECTLINK, ce: TARGET_DIRECTLINK, evo: TARGET_EVO}
    };
})();
