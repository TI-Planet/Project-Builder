// Adapted from https://stackoverflow.com/a/15809374/378298
$.fn.extend({
    filedrop: function (options) {
        const defaults = { callback: null };
        options = $.extend(defaults, options);
        return this.each(function () {
            const $this = $(this);

            // Stop default browser actions
            $this.bind('dragover dragleave', function (event) {
                event.stopPropagation();
                event.preventDefault();
            });

            // Catch drop event
            $this.bind('drop', function (event) {
                // Stop default browser actions
                event.stopPropagation();
                event.preventDefault();

                // Get all files that are dropped
                const files = event.originalEvent.target.files || event.originalEvent.dataTransfer.files;

                // Convert uploaded file to data URL and pass trought callback
                if (options.callback)
                {
                    let i;
                    for (i = 0; i < files.length; i++)
                    {
                        const file = files[i];
                        const reader = new FileReader();
                        reader.onload = (event) => { options.callback(file, event.target.result); };
                        reader.readAsText(file);
                    }
                }
                return false;
            })
        })
    }
});
