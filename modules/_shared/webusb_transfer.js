(() => {
    const state = {
        module: null,
        initPromise: null,
        handle: 0,
        usbListenersBound: false
    };

    async function initModule() {
        if (state.initPromise) {
            return state.initPromise;
        }
        if (typeof TiCalcsModule === "undefined") {
            throw new Error("WebUSB module not loaded");
        }
        state.initPromise = (async () => {
            const module = await TiCalcsModule();
            state.module = module;
            const initResult = await module.ccall('test_init', 'number', [], [], {async: true});
            if (initResult !== 0) {
                console.warn("WebUSB init returned", initResult);
            }
            if (navigator.usb && !state.usbListenersBound) {
                navigator.usb.addEventListener('disconnect', () => {
                    state.handle = 0;
                    state.initPromise = null;
                });
                navigator.usb.addEventListener('connect', () => {
                    state.handle = 0;
                });
                state.usbListenersBound = true;
            }
            return module;
        })();
        return state.initPromise;
    }

    async function ensureHandle() {
        const module = await initModule();
        if (!state.handle) {
            state.handle = module.ccall('test_create_handle', 'number', [], []);
        }
        return state.handle;
    }

    async function ensureDeviceAuthorized(module) {
        if (module.getAuthorizedDevices) {
            const devices = await module.getAuthorizedDevices();
            if (devices && devices.length) {
                return devices[0];
            }
        }
        return module.requestTICalculatorDevice();
    }

    async function sendFileBytes(fileBytes, filename) {
        const module = await initModule();
        if (!module.isWebUSBSupported || !module.isWebUSBSupported()) {
            throw new Error("WebUSB is not supported in this browser");
        }
        if (!module.isSecureContext || !module.isSecureContext()) {
            throw new Error("WebUSB requires HTTPS or localhost");
        }

        await ensureDeviceAuthorized(module);
        if (!module.FS.analyzePath('/uploads').exists) {
            module.FS.mkdir('/uploads');
        }
        const path = `/uploads/${filename}`;
        const data = fileBytes instanceof Uint8Array ? fileBytes : new Uint8Array(fileBytes);
        module.FS.writeFile(path, data);

        let handle = await ensureHandle();
        let result = 0;
        try {
            result = await module.ccall(
                'test_send_file',
                'number',
                ['number', 'string'],
                [handle, path],
                {async: true}
            );
        } catch (err) {
            result = -1;
            console.warn("WebUSB transfer failed, retrying", err);
        }
        if (result !== 0) {
            await module.requestTICalculatorDevice();
            state.handle = 0;
            handle = await ensureHandle();
            result = await module.ccall(
                'test_send_file',
                'number',
                ['number', 'string'],
                [handle, path],
                {async: true}
            );
        }

        try {
            module.FS.unlink(path);
        } catch (cleanupError) {
            console.warn("Failed to clean up upload:", cleanupError);
        }
        return result;
    }

    window.pbWebUsbTransfer = {
        initModule,
        sendFileBytes
    };
})();
