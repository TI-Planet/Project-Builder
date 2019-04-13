var Module;
// From emscripten: in order to reference the preexisting Module var or create it if needed.
if (!Module) Module = (typeof Module !== 'undefined' ? Module : null) || {};

Module['memoryInitializerPrefixURL'] = '/pb/modules/native_eZ80/js/emu/';

Module['preRun'] = function() {

emul_is_inited = false;
emul_is_paused = false;

/* Init C functions wrappers */
initFuncs = function()
{
    pressKey = Module['cwrap']('emu_keypad_event', 'void', ['number', 'number', 'number']);
    sendKey = Module['cwrap']('sendKey', 'void', ['number']);
    slkp = Module['cwrap']('sendLetterKeyPress', 'void', ['number']);
    set_file_to_send = Module['cwrap']('set_file_to_send', 'void', ['string']);
    resetEmul = Module['cwrap']('emu_reset', 'void', []);
}

pauseEmul = function(paused)
{
    emul_is_paused = paused;
    document.getElementById('emu_playpause_btn').className = paused ? 'btn btn-success btn-sm' : 'btn btn-default btn-sm';
    document.getElementById('pauseButtonIcon').className = paused ? 'glyphicon glyphicon-play' : 'glyphicon glyphicon-pause';
    document.getElementById('pauseButtonLabel').innerHTML = paused ? 'Resume' : 'Pause';
    Module['ccall'](paused ? 'emsc_pause_main_loop' : 'emsc_resume_main_loop', 'void', [], []);
}

initLCD = function()
{
    const c = document.getElementById("emu_canvas");

    const w = 320;
    const h = 240;
    c.width = w;
    c.height = h;

    canvasCtx = c.getContext('2d'); // global var
    const imageData = canvasCtx.getImageData(0, 0, w, h);
    const bufSize = w * h * 4;
    const bufPtr = Module['ccall']('lcd_get_frame', 'number');

    repaint = function()
    {
        if (emul_is_paused) { window.requestAnimationFrame(repaint); return; }
        imageData.data.set(Module['HEAPU8'].subarray(bufPtr, bufPtr + bufSize));
        canvasCtx.putImageData(imageData, 0, 0);
        window.requestAnimationFrame(repaint);
    };
    repaint();
}

enableGUI = function()
{
    document.getElementById('varTransferDiv').style.display = 'block';
    document.getElementById('emu_keypad_buttons').style.display = 'block';
    document.getElementById('emu_canvas').style.display = 'block';
    document.getElementById('emu_divider').style.display = 'block';
    document.getElementById('emu_playpause_btn').style.display = 'inline-block';
    document.getElementById('emu_reset_btn').style.display = 'inline-block';
    var docHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
    if (docHeight < 775) {
        document.getElementById('cemu_notice').style.display = 'none';
    }
}
disableGUI = function()
{
    document.getElementById('varTransferDiv').style.display = 'none';
    document.getElementById('emu_keypad_buttons').style.display = 'none';
    document.getElementById('emu_canvas').style.display = 'none';
    document.getElementById('emu_divider').style.display = 'none';
    document.getElementById('emu_playpause_btn').style.display = 'none';
    document.getElementById('emu_reset_btn').style.display = 'none';
    document.getElementById('cemu_notice').style.display = 'inline-block';
}

fileLoaded = function(event, filename, isAutoloadedROM)
{
    if (event.target.readyState == FileReader.DONE)
    {
        var fileAsUint8Array = new Uint8Array(event.target.result);

        FS.writeFile(filename, fileAsUint8Array, {encoding: 'binary'});

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
                Module['ccall']('emsc_cancel_main_loop', 'void', [], []);
            }
            Module['callMain']();

            if (isAutoloadedROM)
            {
                setTimeout(function(){ pauseEmul(true); }, 2000);
            }
        } else {
            if (emul_is_inited) {
                if (emul_is_paused) {
                    pauseEmul(false);
                }
                set_file_to_send(filename);
            } else {
                alert('Please start the emulation with a ROM first!');
            }
        }
    }
}

sendStringKeyPress = function(str)
{
    for (var i=0, delay=0; i < str.length; delay+=250, i++)
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
        return FS.unlink(filename);

    var reader = new FileReader();
    reader.onloadend = function(event) {
        fileLoaded(event, filename, isAutoloadedROM);
    };
    reader.readAsArrayBuffer(file);
}

fileLoadFromInput = function(event)
{
    if (emul_is_inited && emul_is_paused) {
        pauseEmul(false);
    }

    var files = event.target.files;

    document.getElementById('emu_container').style.opacity = .5;
    document.getElementById('emu_container').style.pointerEvents = 'none';
    setTimeout(function() {
        document.getElementById('emu_container').style.opacity = 1;
        document.getElementById('emu_container').style.pointerEvents = 'initial';
    }, files.length*900);

    for (var i=0, delay=0; i<files.length; delay+=900, i++)
    {
        (function(file, delay) {
            setTimeout(function() { fileLoad(file, file.name, false); }, delay);
        })(files[i], delay);
    }
}

} // preRun function
