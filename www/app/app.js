Ext.regApplication({
    name: 'app',
    launch: function() {
        this.launched = true;
        this.mainLaunch();
    },
    mainLaunch: function() {
        if (!device || !this.launched) {return;}
                   console.log('mainLaunch');
                   Ext.util.Observable.capture(new Ext.Panel(), function(){console.log(arguments);})

        this.views.viewport = new this.views.Viewport();
    }
});
