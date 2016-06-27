var Module = { 'memoryInitializerPrefixURL':'modules/native_eZ80/js/emu/', 'preRun': function() {

emul_is_inited = false;
emul_is_paused = false;

/* Init C functions wrappers */
initFuncs = function()
{
    pressKey = Module['cwrap']('keypad_key_event', 'void', ['number', 'number', 'number']);
    sendKey = Module['cwrap']('sendKey', 'void', ['number']);
    slkp = Module['cwrap']('sendLetterKeyPress', 'void', ['number']);
    sendVariable = Module['cwrap']('sendVariableLink', 'number', ['string']);
    resetEmul = Module['cwrap']('emu_reset', 'void', []);
}

pauseEmul = function(paused)
{
    emul_is_paused = paused;
    document.getElementById('emu_playpause_btn').className = paused ? 'btn btn-success btn-sm' : 'btn btn-default btn-sm';
    document.getElementById('pauseButtonIcon').className = paused ? 'glyphicon glyphicon-play' : 'glyphicon glyphicon-pause';
    document.getElementById('pauseButtonLabel').innerHTML = paused ? 'Resume' : 'Pause';
    Module['ccall']('emu_set_emulation_paused', 'void', ['number'], [paused]);
    Module['ccall'](paused ? 'emsc_pause_main_loop' : 'emsc_resume_main_loop', 'void', [], []);
    repaint();
}

initLCD = function()
{
    var c = document.getElementById("emu_canvas");

    var w = 320;
    var h = 240;
    c.width = w;
    c.height = h;

    canvasCtx = c.getContext('2d'); // global var
    var imageData = canvasCtx.getImageData(0, 0, w, h);
    var bufSize = w * h * 4;
    var bufPtr = Module['_malloc'](bufSize);
    var buf = new Uint8Array(Module['HEAPU8']['buffer'], bufPtr, bufSize);

    var wrappedPaint = Module['cwrap']('paintLCD', 'void', ['number']);
    repaint = function()
    {
        if (!emul_is_paused)
        {
            wrappedPaint(buf.byteOffset);
            imageData.data.set(buf);
            canvasCtx.putImageData(imageData, 0, 0);
            window.requestAnimationFrame(repaint);
        }
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
}

disableGUI = function()
{
    document.getElementById('varTransferDiv').style.display = 'none';
    document.getElementById('emu_keypad_buttons').style.display = 'none';
    document.getElementById('emu_canvas').style.display = 'none';
    document.getElementById('emu_divider').style.display = 'none';
    document.getElementById('emu_playpause_btn').style.display = 'none';
    document.getElementById('emu_reset_btn').style.display = 'none';
}

fileLoaded = function(event, filename)
{
    if (event.target.readyState == FileReader.DONE)
    {
        FS.writeFile(filename, new Uint8Array(event.target.result), {encoding: 'binary'});

        if (filename == "CE.rom")
        {
            if (emul_is_inited) {
                Module['ccall']('emsc_cancel_main_loop', 'void', [], []);
            }
            Module['callMain']();
        } else {
            if (emul_is_inited) {
                if (emul_is_paused) {
                    pauseEmul(false);
                }
                sendVariable(filename);
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

fileLoad = function(file, filename)
{
    if (filename.match(/\.rom$/i)) {
        filename = "CE.rom";
    }

    if(!file)
        return FS.unlink(filename);

    var reader = new FileReader();
    reader.onloadend = function(event) {
        fileLoaded(event, filename);
    };
    reader.readAsArrayBuffer(file);
}

fileLoadFromInput = function(event)
{
    var file = event.target.files[0];
    fileLoad(file, file.name);
}

} // preRun function
} // Module
