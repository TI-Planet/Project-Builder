// Adapted from https://stackoverflow.com/a/15809374/378298
$.fn.extend({
    filedrop: function (options) {
        const defaults = { onDrop: null, onEnter: null, onLeave: null, filterFunc: () => true };
        options = $.extend(defaults, options);
        return this.each(function () {
            const $this = $(this);

            $this.bind('dragenter', function(event) {
                event.stopPropagation();
                event.preventDefault();
                options.onEnter && options.onEnter(event);
                return false;
            });

            $this.bind('dragover dragleave', function (event) {
                event.stopPropagation();
                event.preventDefault();
                options.onLeave && options.onLeave(event);
                return false;
            });

            $this.bind('drop', function (event) {
                event.stopPropagation();
                event.preventDefault();
                if (options.onDrop)
                {
                    const files = event.originalEvent.target.files || event.originalEvent.dataTransfer.files || [];
                    Array.prototype.forEach.call(files, (file, i) => {
                        if (options.filterFunc(file.name))
                        {
                            const reader = new FileReader();
                            reader.onload = (event) => { options.onDrop(file, event.target.result, i === files.length - 1); };
                            reader.readAsText(file);
                        }
                    });
                }
                return false;
            })
        })
    }
});
