(function() {
    tinymce.PluginManager.add('shortcode_' + phpvar.name, function(editor) {
        editor.addMenuItem('insert' + phpvar.name, {
            icon: phpvar.icon,
            text: phpvar.title,
            context: 'insert',
            onclick: function() {
                editor.insertContent(phpvar.shortcode);
            }
        });
    });
})();
