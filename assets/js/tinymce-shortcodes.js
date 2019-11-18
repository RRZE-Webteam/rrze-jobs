(function() {
    tinymce.PluginManager.add('jobsshortcodes', function(editor) {
        editor.addMenuItem('insertShortcodesJobs', {
            icon: 'user',
            text: 'Jobs',
            context: 'insert',
            onclick: function() {
                editor.insertContent('[jobs provider=""]<br>');
            }
        });
    });
})();