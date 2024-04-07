window.initWebCEmuUtils = function() {

window.emul_is_inited = false;
window.emul_is_paused = false;

const emuContainer = document.getElementById("emu_container");
const transferProgressIndicator = document.getElementById("emuTransferProgress");

/* Init C functions wrappers */
initFuncs = function()
{
    pressKey = CEmu['cwrap']('emu_keypad_event', 'void', ['number', 'number', 'number']);
    sendKey = CEmu['cwrap']('sendKey', 'void', ['number']);
    slkp = CEmu['cwrap']('sendLetterKeyPress', 'void', ['number']);
    set_file_to_send = CEmu['cwrap']('set_file_to_send', 'void', ['string']);
    resetEmul = CEmu['cwrap']('emu_reset', 'void', []);
}

pauseEmul = function(paused)
{
    emul_is_paused = paused;
    document.getElementById('emu_playpause_btn').className = paused ? 'btn btn-success btn-sm' : 'btn btn-default btn-sm';
    document.getElementById('pauseButtonIcon').className = paused ? 'glyphicon glyphicon-play' : 'glyphicon glyphicon-pause';
    document.getElementById('pauseButtonLabel').innerHTML = paused ? 'Resume' : 'Pause';
    CEmu['ccall'](paused ? 'emsc_pause_main_loop' : 'emsc_resume_main_loop', 'void', [], []);
}

initLCD = function()
{
    const c = document.getElementById("emu_canvas");

    const w = 320;
    const h = 240;
    c.width = w;
    c.height = h;

    const canvasCtx = c.getContext('2d');
    const imageData = canvasCtx.getImageData(0, 0, w, h);
    const bufSize = w * h * 4;
    const bufPtr = CEmu['ccall']('lcd_get_frame', 'number');

    repaint = function()
    {
        if (emul_is_paused) { window.requestAnimationFrame(repaint); return; }
        imageData.data.set(CEmu['HEAPU8'].subarray(bufPtr, bufPtr + bufSize));
        canvasCtx.putImageData(imageData, 0, 0);
        window.requestAnimationFrame(repaint);
    };
    repaint();
}

enableGUI = function()
{
    document.getElementById('varTransferDiv').style.display = 'inline-block';
    document.getElementById('emu_intro').style.display = 'none';
    document.getElementById('emu_keypad_buttons').style.display = 'block';
    document.getElementById('emu_canvas').style.display = 'block';
    document.getElementById('emu_divider').style.display = 'block';
    document.getElementById('emu_playpause_btn').style.display = 'inline-block';
    document.getElementById('emu_reset_btn').style.display = 'inline-block';
    const docHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
    if (docHeight < 775) {
        document.getElementById('cemu_notice').style.display = 'none';
    }
}
disableGUI = function()
{
    document.getElementById('varTransferDiv').style.display = 'none';
    document.getElementById('emu_intro').style.display = 'inline-block';
    document.getElementById('emu_keypad_buttons').style.display = 'none';
    document.getElementById('emu_canvas').style.display = 'none';
    document.getElementById('emu_divider').style.display = 'none';
    document.getElementById('emu_playpause_btn').style.display = 'none';
    document.getElementById('emu_reset_btn').style.display = 'none';
    document.getElementById('cemu_notice').style.display = 'inline-block';
}

fileLoaded = function(event, filename, isAutoloadedROM)
{
    if (event.target.readyState === FileReader.DONE)
    {
        const fileAsUint8Array = new Uint8Array(event.target.result);

        CEmu["FS"].writeFile(filename, fileAsUint8Array, {encoding: 'binary'});

        if (filename === "CE.rom")
        {
            // If the ROM already came from the local browser storage, don't re-save it.
            if (!isAutoloadedROM)
            {
                localforage.setItem('ce_rom', fileAsUint8Array)
                           .then(function() { console.log("ROM saved locally"); })
                           .catch(function(err) { console.log("Error while saving locally the ROM", err); });
            }

            if (emul_is_inited) {
                CEmu['ccall']('emsc_cancel_main_loop', 'void', [], []);
            }
            CEmu['callMain']();

            if (isAutoloadedROM)
            {
                setTimeout(function(){ pauseEmul(true); }, 3000);
            }

            setTimeout(function()
            {
                let el = document.getElementById('emu_keypad_buttons');
                el.className = CEmu['ccall']('get_device_type', 'number') === 1 ? "ti83pce" : "ti84pce";
            }, 1000);
        } else {
            if (emul_is_inited) {
                if (emuContainer) {
                    emuContainer.style.opacity = '0.5';
                    emuContainer.style.pointerEvents = 'none';
                }
                if (transferProgressIndicator) {
                    transferProgressIndicator.value = "0";
                    transferProgressIndicator.parentElement.style.display = 'block';
                }
                if (emul_is_paused) {
                    pauseEmul(false);
                }
                if (CEmu['_emsc_set_main_loop_timing']) CEmu['_emsc_set_main_loop_timing'](0, 0); // EM_TIMING_SETTIMEOUT, as fast as possible.
                set_file_to_send(filename);
            } else {
                alert('Please start the emulation with a ROM first!');
            }
        }
    }
}

sendStringKeyPress = function(str)
{
    let i = 0, delay = 0;
    for (; i < str.length; delay+=250, i++)
    {
        (function(char, delay) {
            setTimeout(function() { slkp(char.charCodeAt(0)); }, delay);
        })(str[i], delay);
    }
}

drawLCDOff = function()
{
    canvasCtx.fillStyle = "black";
    canvasCtx.fillRect(0, 0, 320, 240);
    //canvasCtx.fillStyle = "white";
    //canvasCtx.fillText("LCD Off", 120, 230);
}

fileLoad = function(file, filename, isAutoloadedROM)
{
    if (filename.match(/\.rom$/i)) {
        filename = "CE.rom";
    }

    if(!file)
        return CEmu["FS"].unlink(filename);

    const reader = new FileReader();
    reader.onloadend = function(event) {
        fileLoaded(event, filename, isAutoloadedROM);
    };
    reader.readAsArrayBuffer(file);
}

transferProgressCallback = function(val, max)
{
    if (window.emul_file_load_progress_extcb) emul_file_load_progress_extcb(val, max);
    if (val === 1 && max === 1) {
        console.log("[CEmu] file transfer done.");
        if (CEmu['_emsc_set_main_loop_timing']) CEmu['_emsc_set_main_loop_timing'](0, 1000/60); // EM_TIMING_SETTIMEOUT, 60fps.
        if (window.emul_file_load_done_extcb) emul_file_load_done_extcb();
        if (transferProgressIndicator) transferProgressIndicator.parentElement.style.display = 'none';
        if (emuContainer) {
            emuContainer.style.opacity = '1';
            emuContainer.style.pointerEvents = 'initial';
        }
    } else {
        if (transferProgressIndicator) transferProgressIndicator.value = (val*100/max).toFixed(0);
    }
}

transferErrorCallback = function()
{
    console.log("[CEmu] error during file transfer");
    if (CEmu['_emsc_set_main_loop_timing']) CEmu['_emsc_set_main_loop_timing'](0, 1000/60); // EM_TIMING_SETTIMEOUT, 60fps.
    if (window.emul_file_load_error_extcb) emul_file_load_error_extcb();
    if (transferProgressIndicator) transferProgressIndicator.parentElement.style.display = 'none';
    if (emuContainer) {
        emuContainer.style.opacity = '1';
        emuContainer.style.pointerEvents = 'initial';
    }
}

fileLoadFromInput = function(event)
{
    if (emul_is_inited && emul_is_paused) {
        pauseEmul(false);
    }

    const files = event.target.files;

    let i = 0, delay = 0;
    for (; i<files.length; delay+=900, i++)
    {
        (function(file, delay) {
            setTimeout(function() { fileLoad(file, file.name, false); }, delay);
        })(files[i], delay);
    }

    event.target.value = null;
}

console.log("initWebCEmuUtils done");

}
