;(function( window, $, undefined ){

    window.wickedfolders = window.wickedfolders || { views: {}, models: {}, collections: {} };

    wickedfolders.models.Model = Backbone.Model.extend();

    wickedfolders.models.Folder = wickedfolders.models.Model.extend({
        defaults: {
            parent:     'root',
            postType:   false,
            taxonomy:   false,
            posts:      false,
            type:       'Wicked_Folders_Term_Folder'
        },

        initialize: function(){

            if ( ! this.get( 'posts' ) ) {
                this.set( 'posts', new wickedfolders.collections.Posts() );
            }

        },

        parse: function( response, options ){
            return _.omit( response, 'posts' );
        },

        url: function(){

            // NOTE: the _methodOverride parameter is being used an an alternative
            // to overriding the sync function

            var id = this.id ? this.id : '',
                taxonomy = this.get( 'taxonomy' ),
                action = this.get( '_actionOverride' ) || 'wicked_folders_save_folder',
                methodOverride = this.get( '_methodOverride' ) || false;

            // We need the ID and taxonomy attributes when deleting
            var url = 'admin-ajax.php?action=' + action + '&id=' + id + '&taxonomy=' + taxonomy;

            // Assume we're updating if we have an ID
            if ( false === methodOverride && id ) methodOverride = 'PUT';

            if ( false !== methodOverride ) url += '&_method_override=' + methodOverride;

            return url;
        }

    });

    wickedfolders.models.Post = wickedfolders.models.Model.extend({
        defaults: {
            name: false,
            type: false
        },

        url: function(){
            return 'admin-ajax.php?action=wicked_folders_save_post';
        }

    });

    wickedfolders.models.FolderTreeState = wickedfolders.models.Model.extend({
        defaults: {
            selected:           '0',
            expanded:           [ '0' ],
            checked:            [],
            readOnly:           false,
            showFolderContents: false
        },

        addExpanded: function( id ){

            var expanded = _.clone( this.get( 'expanded' ) );

            expanded.push( id );

            this.set( 'expanded', _.uniq( expanded ) );

        },

        toggleExpanded: function( id ){

            var expanded = _.clone( this.get( 'expanded' ) );

            if ( -1 == expanded.indexOf( id ) ) {
                expanded.push( id );
            } else {
                expanded = _.without( expanded, id );
            }

            this.set( 'expanded', expanded );

        },

        toggleChecked: function( id ){

            var checked = _.clone( this.get( 'checked' ) ) || [];

            if ( -1 == checked.indexOf( id ) ) {
                checked.push( id );
            } else {
                checked = _.without( checked, id );
            }

            this.set( 'checked', checked );

        }

    });

    wickedfolders.collections.Posts = Backbone.Collection.extend({
        model: wickedfolders.models.Post,
    });

    wickedfolders.collections.Folders = Backbone.Collection.extend({
        model: wickedfolders.models.Folder,

        children: function( parent ) {
            var folders = new wickedfolders.collections.Folders();
            this.each( function( folder ) {
                if ( folder.get( 'parent' ) == parent ) {
                    folders.add( folder );
                }
            });
            return folders;
        },

        ancestors: function( id ) {

            var ancestors = new wickedfolders.collections.Folders(),
                folder = this.get( id );

            if ( folder ) {
                var parent = this.findWhere( { id: folder.get( 'parent' ) } );
                if ( parent ) {
                    ancestors.add( parent );
                    var parentAncestors = this.ancestors( parent.id );
                    parentAncestors.each( function( ancestor ){
                        ancestors.add( ancestor );
                    } );
                }
            }

            return ancestors;

        },

        ancestorIds: function( id ){
            return this.ancestors( id ).pluck( 'id' );
        }

    });

    wickedfolders.views.View = Backbone.View.extend({
        constructor: function( options ) {
            if ( this.options ) {
                options = _.extend( {} , _.result( this, 'options' ), options );
            }
            this.options = options;
            Backbone.View.prototype.constructor.apply( this, arguments );
        }
    });

    wickedfolders.views.FolderSelect = wickedfolders.views.View.extend({
        tagName:    'select',

        attributes: function(){
            return {
                'name': this.options.name
            }
        },

        constructor: function( options ){

            _.defaults( options, {
                selected:       '0',
                name:           'wicked_folder_parent',
                defaultText:    wickedFoldersL10n.folderSelectDefault
            } );

            wickedfolders.views.View.prototype.constructor.apply( this, arguments );

        },

        initialize: function(){
            var render = _.debounce( this.render );
            this.collection.on( 'add remove reset change', render, this );
        },

        render: function(){
            var id = this.$el.val();
            this.$el.empty();
            this.$el.append( '<option value="0">' + this.options.defaultText + '</option>' );
            this.renderOptions( '0' );
            // Make sure the option still exists
            if ( this.$( '[value="' + id + '"]' ).length ) {
                this.$el.val( id );
            } else {
                this.$el.val( '0' );
            }
            return this;

        },

        renderOptions: function( parent, depth ){

            if ( ! parent ) parent = 'root';
            if ( typeof depth == 'undefined' ) depth = 0;

            var view = this,
                space = '&nbsp;&nbsp;&nbsp;';

            this.collection.each( function( folder ) {
                if ( folder.get( 'parent' ) == parent ) {
                    var option = $( '<option />' );
                    option.attr( 'value', folder.id );
                    //option.html( space.repeat( depth ) + folder.get( 'name' ) );
                    option.html( repeat( space, depth ) + folder.get( 'name' ) );
                    if ( view.options.selected == folder.id ) {
                        option.prop( 'selected', true );
                    }
                    view.$el.append( option );
                    view.renderOptions( folder.id, ( depth + 1 ) );
                }
            });

            function repeat( s, n ){
                if ( n < 1 ) return '';
                for ( i = 0; i < n - 1; i++ ) {
                    s += s;
                }
                return s;
            }
        },

    });

    wickedfolders.views.FolderPath = wickedfolders.views.View.extend({
        tagName:    'ul',
        className:  'wicked-folder-path',

        initialize: function(){

            this.collection.on( 'change:name', this.render, this );

        },

        render: function() {

            var selected = this.options.selected || '0',
                selectedFolder = this.collection.get( selected ),
                ancestors = this.collection.ancestors( selected ),
                view = this;

            view.$el.empty();

            ancestors.chain().reverse().each( function( folder ){
                var a = $( '<a class="wicked-folder" href="#" />' ),
                    li = $( '<li />' );

                a.text( folder.get( 'name') );
                li.attr( 'data-folder-id', folder.id );
                li.append( a );
                view.$el.append( li );
            } );

            var li = $( '<li />' );
            li.attr( 'data-folder-id', selectedFolder.id );
            li.text( selectedFolder.get( 'name') );
            view.$el.append( li );

        }

    });

    wickedfolders.views.FolderTree = wickedfolders.views.View.extend({
        tagName:    'div',
        className:  'wicked-folder-tree',

        events: {
            'change [type="checkbox"]': 'toggleCheckbox',
            'click .wicked-toggle':     'toggleBranch',
            'click .wicked-folder':     'clickFolder',
        },

        initialize: function( options ) {

            _.defaults( options, {
                model:          false,
                showCheckboxes: false
            } );

            if ( ! options.model ) {
                this.model = new wickedfolders.models.FolderTreeState();
            }

            this.model.on( 'change:selected', this.changeSelected, this );
            this.model.on( 'change:expanded', this.changeExpanded, this );
            this.model.on( 'change:checked', this.changeChecked, this );

        },

        clickFolder: function( e ){

            var id = $( e.currentTarget ).parent().attr( 'data-folder-id' );

            if ( ! this.options.showCheckboxes ) {
                this.model.set( 'selected', id );
                this.model.addExpanded( id );
            }

        },

        changeSelected: function(){

            var selected = this.model.get( 'selected' );

            this.$( 'li' ).removeClass( 'wicked-selected' );

            this.$( 'li[data-folder-id="' + selected + '"]' ).addClass( 'wicked-selected' );

        },

        changeExpanded: function(){

            var expanded = this.model.get( 'expanded' );

            this.$( 'li' ).removeClass( 'wicked-expanded' );

            _.each( expanded, function( id ){
                this.$( 'li[data-folder-id="' + id + '"]' ).addClass( 'wicked-expanded' );
            }, this );

        },

        changeChecked: function(){

            var checked = this.model.get( 'checked' );

            _.each( this.$( 'input[type="checkbox"]' ), function( item ){
                var id = $( item ).val().toString();
                if ( -1 == checked.indexOf( id ) ) {
                    $( item ).prop( 'checked', false );
                } else {
                    $( item ).prop( 'checked', true );
                }
            });

        },

        toggleBranch: function( e ) {

            var id = $( e.currentTarget ).parent().attr( 'data-folder-id' );

            this.model.toggleExpanded( id );

        },

        toggleCheckbox: function( e ){

            this.model.toggleChecked( $( e.currentTarget ).val() );

        },

        render: function() {

            // Build the tree
            var branch = this.branch( 'root' );

            branch.$el.addClass( 'wicked-tree' );

            this.$el.html( branch.el );

            return this;

        },

        branch: function( parent ) {
            if ( ! parent ) parent = 'root';

            var FoldersCollection = wickedfolders.collections.Folders,
                FolderLeaf = wickedfolders.views.FolderTreeLeaf,
                PostLeaf = wickedfolders.views.PostTreeLeaf,
                Folder = wickedfolders.views.FolderTreeFolder,
                FolderTreePost = wickedfolders.views.FolderTreePost,
                TreeBranch = wickedfolders.views.FolderTreeBranch,
                TreeBranchToggle = wickedfolders.views.FolderTreeBranchToggle,
                view = this,
                selected = this.model.get( 'selected' ),
                expanded = this.model.get( 'expanded' ),
                checked = this.model.get( 'checked' ) || [],
                readOnly = this.model.get( 'readOnly' ),
                showCheckboxes = this.options.showCheckboxes,
                branch;

            var branch = new TreeBranch({ collection: new FoldersCollection() });

            this.collection.each( function( folder ) {
                if ( folder.get( 'parent' ) == parent ) {

                    branch.collection.add( folder );

                    var isFolderChecked = -1 != checked.indexOf( folder.id );
                    var leaf            = new FolderLeaf({ model: folder });
                    var folderView      = new Folder({
                        model:          folder,
                        tagName:        showCheckboxes ? 'label' : 'a',
                        showCheckbox:   showCheckboxes,
                        readOnly:       readOnly,
                        checked:        isFolderChecked
                    });
                    var toggle          = new TreeBranchToggle();
                    var childBranch     = view.branch( folder.id );

                    if ( _.contains( expanded, folder.id ) ) {
                        leaf.$el.addClass( 'wicked-expanded' );
                    }

                    if ( selected == folder.id ) {
                        leaf.$el.addClass( 'wicked-selected' );
                    }

                    if ( -1 != checked.indexOf( folder.id ) ) {
                        //folderView.$( 'input[type="checkbox"]' ).prop( 'checked', true );
                        leaf.$( 'input[type="checkbox"]' ).prop( 'checked', true );
                    }

                    leaf.$el.append( toggle.el );
                    leaf.$el.append( folderView.el );

                    if ( childBranch.collection.length || ( view.model.get( 'showFolderContents' ) && folder.get( 'posts' ).length ) ) {
                        leaf.$el.append( childBranch.el );
                    }

                    if ( view.model.get( 'showFolderContents' ) ) {
                        folder.get( 'posts' ).each( function( post ){
                            var postLeaf = new PostLeaf({ model: post });
                            var postView = new FolderTreePost({ model: post });
                            postLeaf.$el.append( postView.el );
                            childBranch.$el.append( postLeaf.el );
                        } );
                    }

                    branch.$el.append( leaf.el );
                }
            });

            return branch;
        },

        /**
         * Expands the tree to the selected folder.
         */
        expandToSelected: function(){
            var selected = this.model.get( 'selected' ),
                expanded = _.clone( this.model.get( 'expanded' ) ),
                ancestors = this.collection.ancestorIds( selected );

            this.model.set( 'expanded', _.union( expanded, ancestors ) );
        }

    });

    wickedfolders.views.FolderTreeBranch = wickedfolders.views.View.extend({
        tagName: 	'ul',
        initialize: function() {
            //this.render();
        },
        render: function() {
            var FolderView = wickedfolders.views.FolderTreeFolder;
            var view = this;
            this.collection.each( function( folder ) {
                var folderView = new FolderView({ model: folder });
                view.$el.append( folderView.el );
            });
        }
    });

    wickedfolders.views.FolderTreeLeaf = wickedfolders.views.View.extend({
        tagName: 	'li',
        className: 	function() {

            var classes = 'wicked-tree-leaf wicked-folder-leaf';

            if ( 'Wicked_Folders_Term_Folder' == this.model.get('type') ) {
                classes += ' wicked-movable';
            }

            return classes;

        },

        attributes: function() {
            return {
                'data-folder-id': this.model.id
            }
        }/*,

        initialize: function( options ) {

            _.defaults( options, {
                showCheckbox:   false,
                readOnly:       false,
            } );

            if ( ! this.$( '.wicked-checkbox' ).length && this.options.showCheckbox ) {
                this.$el.prepend( '<span class="wicked-checkbox"><input type="checkbox" name="wicked_folder[]" value="' + this.model.id + '" /></span>' );
            }

            this.$( '.wicked-checkbox input' ).prop( 'disabled', this.options.readOnly );

        }*/

    });

    wickedfolders.views.PostTreeLeaf = wickedfolders.views.View.extend({
        tagName: 	'li',
        className: 	'wicked-tree-leaf wicked-post-leaf',
        attributes: function() {
            return {
                'data-post-id': this.model.id
            }
        }
    });

    wickedfolders.views.FolderTreeBranchToggle = wickedfolders.views.View.extend({
        tagName: 	'a',
        className: 	'wicked-toggle',
        attributes: {
            href: '#'
        },
        events: {
            'click': 'click'
        },
        click: function( e ){
            e.preventDefault();
        }
    });

    wickedfolders.views.FolderTreeFolder = wickedfolders.views.View.extend({
        tagName: 	'a',
        className: 	'wicked-folder',
        attributes: function(){
            var atts = {};
            if ( 'a' == this.tagName ) atts['href'] = '#';
            return atts;
        },

        events: {
            'click': 'click'
        },

        initialize: function( options ) {

            _.defaults( options, {
                showCheckbox:   false,
                readOnly:       false,
                checked:        false
            } );

            this.render();

            this.model.on( 'change:name', this.render, this );

        },

        click: function( e ){
            if ( 'a' == this.tagName ) e.preventDefault();
        },

        render: function() {
            this.$el.text( this.model.get( 'name' ) );
            if ( ! this.$( '.wicked-icon' ).length ) {
                this.$el.prepend( '<span class="wicked-icon" />' );
            }
            if ( ! this.$( '.wicked-checkbox' ).length && this.options.showCheckbox ) {
                this.$el.prepend( '<span class="wicked-checkbox"><input type="checkbox" name="wicked_folder[]" value="' + this.model.id + '" /></span>' );
            }
            this.$( '.wicked-checkbox input' ).prop( 'checked', this.options.checked );
            this.$( '.wicked-checkbox input' ).prop( 'disabled', this.options.readOnly );
        }

    });

    wickedfolders.views.FolderTreePost = wickedfolders.views.View.extend({
        tagName: 	'a',
        className: 	'wicked-post',
        attributes: {
            href: '#'
        },

        events: {
            'click': 'click'
        },

        initialize: function() {

            this.render();

        },

        click: function( e ){
            e.preventDefault();
        },

        render: function() {
            this.$el.text( this.model.get( 'name' ) );
        }

    });

    wickedfolders.models.FolderBrowserController = wickedfolders.models.Model.extend({

        _saveStateTimer: null,

        defaults: {
            id:                     1,
            folders:                false,
            folder:                 false,
            expanded:               [ '0' ],
            postType:               false,
            taxonomy:               false,
            loading:                false,
            treePaneWidth:          400,
            showContentsInTreeView: false,
            hideAssignedItems:      true
        },

        initialize: function(){

            this.on( 'change', this.saveState, this );

        },

        saveState: function(){

            var model = this;

            clearTimeout( this._saveStateTimer );

            // Wait a second before saving in case another action triggers
            // a save
            this._saveStateTimer = setTimeout( function(){
                model.save();
            }, 1000 );

        },

        moveObject: function( objectType, objectId, destinationObjectId, sourceFolderId ) {

            // TODO: probably a better way to handle all of this...

            var model = this;

            model.set( 'loading', true );

            $.ajax(
                ajaxurl,
                {
                    data: {
                        'action':                   'wicked_folders_move_object',
                        //'nonce': WickedFolderSettings.moveObjectNonce,
                        'object_type':              objectType,
                        'object_id':                objectId,
                        'destination_object_id':    destinationObjectId,
                        'source_folder_id':         sourceFolderId
                    },
                    method: 'POST',
                    dataType: 'json',
                    success: function( data ) {

                        model.set( 'loading', false );

                    },
                    error: function( data ) {

                        model.set( 'loading', false );

                    }
                }
            );

        },

        url: function(){
            return 'admin-ajax.php?action=wicked_folders_save_state';
        }

    });

    wickedfolders.models.FolderState = wickedfolders.models.Model.extend({
        defaults: {
            //page: 1,
            //pages: 1,
            //order:          'ASC',
            //orderby:        'title'
            needsUpdate:    true,
            content:        '',
            selected:       []
        },
    });

    wickedfolders.views.FolderDialog = wickedfolders.views.View.extend({

        folderSelectView: false,

        events: {
            'click .wicked-popup-mask':             'onClose',
            'click .wicked-popup-close':            'onClose',
            'click .wicked-cancel':                 'onClose',
            'keyup [name="wicked_folder_name"]':    'setSaveButtonState',
            'blur [name="wicked_folder_name"]':     'setSaveButtonState',
            'submit form':                          'save'
        },

        initialize: function( options ){

            _.defaults( options, {
                mode: 'edit'
            } );

            this.folderSelectView = new wickedfolders.views.FolderSelect({
                collection:     this.collection,
                selected:       this.model.get( 'parent' ),
                defaultText:    '&mdash; ' + wickedFoldersL10n.folderSelectDefault + ' &mdash;'
            });

            this.setFolder( this.model );

        },

        setFolder: function( folder ){

            this.model = folder;

            this.model.on( 'request', function(){
                this.$( '.wicked-save' ).prop( 'disabled', true );
            }, this );

        },

        render: function(){

            var view = this,
                template = _.template( $( '#tmpl-wicked-folder-dialog' ).html() ),
                mode = this.options.mode,
                saveButtonLabel = wickedFoldersL10n.save,
                title = wickedFoldersL10n.editFolderLink;

            if ( 'add' == mode ) {
                title = wickedFoldersL10n.addNewFolderLink;
            }

            if ( 'delete' == mode ) {
                title           = wickedFoldersL10n.deleteFolderLink;
                saveButtonLabel = wickedFoldersL10n.delete;
            }

            template = template({
                mode:                       mode,
                dialogTitle:                title,
                folderName:                 this.model.get( 'name' ),
                saveButtonLabel:            saveButtonLabel,
                deleteFolderConfirmation:   wickedFoldersL10n.deleteFolderConfirmation
            });

            this.folderSelectView.options.selected = this.model.get( 'parent' );

            this.$el.html( template );
            //this.$( '.wicked-folder-parent' ).html( this.folderSelectView.render() );
            this.$( '.wicked-folder-parent' ).html( this.folderSelectView.render().el );

            this.setSaveButtonState();

        },

        onClose: function( e ){

            e.preventDefault();

            this.close();

        },

        open: function(){

            this.$( '.wicked-popup-mask' ).show();
            this.$( '.wicked-popup' ).show();

            if ( 'delete' != this.options.mode ) {
                this.$( '[name="wicked_folder_name"]' ).get( 0 ).focus();
            }

        },

        close: function(){

            this.$( '.wicked-popup-mask' ).hide();
            this.$( '.wicked-popup' ).hide();

        },

        save: function( e ){

            var view = this;

            e.preventDefault();

            if ( 'delete' == this.options.mode ) {
                this.model.set( '_methodOverride', 'DELETE' );
                this.model.destroy( {
                    success: function(){
                        view.close();
                    }
                } );

            } else {

                this.model.set( {
                    name:   this.$( '[name="wicked_folder_name"]' ).val(),
                    parent: this.$( '[name="wicked_folder_parent"]' ).val()
                } );

                this.model.save( {}, {
                    success: function( model, response, options ){
                        view.setSaveButtonState();
                        view.close();
                        if ( 'add' == view.options.mode ) {
                            view.collection.add( view.model );
                        }
                    },
                    error: function( model, response, options ){
                        view.$( '.wicked-errors' ).text( response.responseJSON.message ).show();
                        view.setSaveButtonState();
                    }
                } );

            }

        },

        setSaveButtonState: function(){

            var disabled = false;

            if ( 'delete' != this.options.mode ) {
                if ( this.$( '[name="wicked_folder_name"]' ).val().length < 1 ) {
                    disabled = true;
                }
            }

            this.$( '.wicked-save' ).prop( 'disabled', disabled );

        }

    });

    wickedfolders.views.FolderBrowser = wickedfolders.views.View.extend({
        folderDialogView:   false,
        folderTreeView:     false,
        folderStates:       false,

        events: {
            'click .wicked-folder':                     'clickFolder',
            'click .wicked-add-new-folder':             'addNewFolder',
            'click .wicked-edit-folder':                'editFolder',
            'click .wicked-delete-folder':              'deleteFolder',
            'click .wicked-expand-all':                 'expandAllFolders',
            'click .wicked-collapse-all':               'collapseAllFolders',
            'change .check-column [type="checkbox"]':   'togglePostSelection',
            'change #wicked-hide-assigned':             'toggleHideAssigned',
            'submit form':                              'formSubmitted'
        },

        initialize: function(){

            var view = this,
                folderStates = new Backbone.Collection(),
                expanded = this.model.get( 'expanded' ),
                folder = this.model.get( 'folder' );

            // Make sure the selected folder's ancestors are expanded upon
            // initialization to ensure the selected folder is visible in the
            // tree
            var ancestors = this.folders().ancestors( folder.id );

            ancestors.each( function( ancestor ){
                expanded.push( ancestor.id );
            } );

            expanded = _.uniq( expanded );

            // Initialize folder tree view
            var folderTree = new wickedfolders.views.FolderTree({
                collection: this.model.get( 'folders' ),
                model:      new wickedfolders.models.FolderTreeState({
                    selected:           folder.id,
                    expanded:           expanded,
                    showFolderContents: this.model.get( 'showContentsInTreeView' ),
                })
            });

            this.folderTreeView = folderTree;

            // Set up folder states
            this.folders().each( function( folder ){
                folderStates.add( new wickedfolders.models.FolderState({
                    id:             folder.id,
                    needsUpdate:    folder.id != view.folder().id
                }) );
                folder.get( 'posts' ).on( 'add', view.folderPostAddedOrRemoved, view );
                folder.get( 'posts' ).on( 'remove', view.folderPostAddedOrRemoved, view );
            });

            // Initialize the current folder's content
            folderStates.get( view.folder().id ).set( 'content', this.$( '.wicked-folder-contents-pane' ).html() );

            this.folderStates = folderStates;

            // Bind events
            this.folderStates.on( 'change:selected', this.updateFolderStateSelections, this );
            this.folders().on( 'add', this.folderAdded, this );
            this.folders().on( 'remove', this.folderRemoved, this );
            this.folders().on( 'change:parent', this.folderParentChanged, this );
            //this.folders().get( 'posts' ).on( 'add', this.folderPostAddedOrRemoved, this );
            //this.folders().get( 'posts' ).on( 'remove', this.folderPostAddedOrRemoved, this );
            this.model.on( 'change:folder', this.folderChanged, this );
            this.model.on( 'change:loading', this.setLoadingMask, this );
            this.model.on( 'moveObjectStart', this.onMoveObjectStart, this );
            this.folderTreeView.model.on( 'change:expanded', this.changeExpanded, this );

            this.folderDialogView = new wickedfolders.views.FolderDialog({
                model:      this.folder(),
                collection: this.folders()
            });

            this.render();

        },

        /**
         * Returns the folder currently being viewed.
         */
        folder: function(){
            return this.model.get( 'folder' );
        },

        /**
         * Returns the view's folders collection.
         */
        folders: function(){
            return this.model.get( 'folders' );
        },

        /**
         * Returns the view's folder states.
         */
        folderState: function() {
            return this.folderStates.get( this.folder().id );
        },

        changeExpanded: function() {

            // Keep the controller's expanded state in sync with the folder
            // tree's state
            this.model.set( 'expanded', this.folderTreeView.model.get( 'expanded' ) );

        },

        /**
         * Change handler for post row select checkboxes.
         */
        togglePostSelection: function( e ){

            var checkbox = $( e.currentTarget ),
                row = checkbox.parents( 'tr' ),
                table = this.$( '.wicked-folder-contents-pane .wp-list-table' ),
                ids = _.clone( this.folderState().get( 'selected' ) );

            if ( -1 == checkbox.attr( 'id' ).indexOf( 'select-all' ) ) {
                id = row.attr( 'id' ).substring( 5 );
                if ( checkbox.prop( 'checked' ) ) {
                    ids.push( id );
                } else {
                    ids = _.without( ids, id );
                }
            } else {
                // Handle select all checkboxes
                if ( checkbox.prop( 'checked' ) ) {
                    table.find( 'tbody tr' ).each( function( index, row ){
                        ids.push( $( row ).find( '.check-column [type="checkbox"]' ).val() );
                    } );
                } else {
                    ids = [];
                }
            }

            this.folderState().set( 'selected', ids );

        },

        toggleHideAssigned: function( e ){

            this.model.set( 'hideAssignedItems', $( e.currentTarget ).prop( 'checked' ) );

            this.folderState().set( 'needsUpdate', true ),
            this.folderChanged();

        },

        updateFolderStateSelections: function() {

            var selected = this.folderState().get( 'selected' ),
                disableMoveMultiple = this.folderState().get( 'selected' ).length < 1,
                table = this.$( '.wicked-folder-contents-pane .wp-list-table' ),
                isMedia = table.hasClass( 'media' ),
                view = this;

            // Clear out items from the move multiple container
            this.$( '.wicked-move-multiple .wicked-items' ).empty();

            // Reset all to unchecked
            table.find( 'tbody .check-column [type="checkbox"]' ).prop( 'checked', false );

            _.each( selected, function( id ){
                table.find( 'tbody .check-column [type="checkbox"][value="' + id + '"]' ).prop( 'checked', true );
            } );

            checkedRows = this.$( '.wicked-folder-contents-pane tbody .check-column [type="checkbox"]:checked' ).parents( 'tr' )

            table.find( 'tbody .check-column [type="checkbox"]' ).each( function( index, checkbox ){

                var checkbox = $( checkbox ),
                    id = checkbox.val(),
                    row = checkbox.parents( 'tr' );

                // Get the post's name
                if ( isMedia ) {
                    var name = $( row ).find( '.title a' ).eq( 0 ).text().trim();
                } else {
                    var name = $( row ).find( '.row-title' ).text().trim();
                }

                if ( checkbox.prop( 'checked' ) ) {

                    // Append a div with the post's name to the move multiple
                    // container so that the post names are displayed when moving
                    // multiple posts
                    _.each( checkedRows, function( row ){
                        $( row ).find( '.wicked-move-multiple .wicked-items' ).append( '<div data-object-id="' + id + '">' + name + '</div>' );
                    });
                    table.find( 'thead .wicked-move-multiple .wicked-items' ).append( '<div data-object-id="' + id + '">' + name + '</div>' );
                } else {
                    view.$( '.wicked-move-multiple[data-object-id="' + id + '"] .wicked-items' ).append( '<div data-object-id="' + id + '">' + name + '</div>' );
                }

            } );

            view.$( 'th .wicked-move-multiple' ).draggable( 'option', 'disabled', disableMoveMultiple );

        },

        clickFolder: function( e ) {

            var id = $( e.currentTarget ).parent().attr( 'data-folder-id' ),
                folder = this.model.get( 'folders' ).get( id );

            this.model.set( 'folder', folder );

        },

        folderPostAddedOrRemoved: function( post ){

            this.renderFolderTree();

        },

        folderAdded: function( folder ){

            this.folderStates.add( new wickedfolders.models.FolderState({
                id:             folder.id,
                needsUpdate:    true
            }) );

            this.renderFolderTree();

        },

        folderRemoved: function( folder ){

            var parent = this.folders().get( folder.get( 'parent' ) );

            // If the current folder was removed, switch to the parent folder
            if ( this.folder().id == folder.id ) {
                this.model.set( 'folder', parent );
            }

            // Move the deleted folder's children to it's parent.  Note: the
            // backend takes care of updating the children's parent when the
            // folder is removed so keep this silent
            var children = this.folders().where( { parent: folder.id } );

            _.each( children, function( child ){
                child.set( 'parent', parent.id, { silent: true } );
            } );

            this.renderFolderTree();

        },

        folderChanged: function(){

            var view = this,
                id = this.model.get( 'folder' ).get( 'id' ),
                folderType = this.model.get( 'folder' ).get( 'type' ),
                folderState = this.folderStates.get( id ),
                router = this.model.get( 'router' ),
                s = this.$( '#post-search-input' ).val(),
                postType = this.model.get( 'postType' );

            this.$el.attr( 'data-folder', id );
            this.$( '[name="folder"]' ).val( id );

            this.renderFolderPath();
            this.renderActions();
            this.folderTreeView.model.set( 'selected', id );

            if ( folderState.get( 'needsUpdate' ) ) {

                this.model.set( 'loading', true );

                // Prevent hide assigned checkbox from triggering additional loads
                this.$( '#wicked-hide-assigned' ).prop( 'disabled', true );

                /*
                , {
                    post_type:  view.model.get( 'postType' ),
                    page:       'page_' + view.model.get( 'postType' ) + '_folders',
                    folder:     id
                }*/
                var url = 'edit.php?page=wicked_' + view.model.get( 'postType' ) + '_folders&folder=' + id + '&folder_type=' + folderType;
                // post_type parameter in URL causes conflicts in WordPRess for
                // 'post' types
                if ( 'post' != postType ) url += '&post_type=' + postType;

                url += '&hide_assigned=' + ( this.model.get( 'hideAssignedItems' ) ? 1 : 0 );

                if ( s ) url += '&s=' + s;

                $.get( url, function( data ){
                    data = $( '<div />' ).append( data );
                    folderState.set( 'content', $( data ).find( '#wicked-folder-browser .wicked-folder-contents-pane' ).html() );
                    folderState.set( 'needsUpdate', false );
                    view.renderFolderContent();
                    view.model.set( 'loading', false );

                    // Re-enable hide assigned checkbox
                    view.$( '#wicked-hide-assigned' ).prop( 'disabled', false );

                } );
                /*
                $.get( ajaxurl, {
                    action:     'wicked_folders_get_contents',
                    post_type:  view.model.get( 'postType' ),
                    paged:      folderState.get( 'page' ),
                    orderby:    folderState.get( 'orderby' ),
                    order:      folderState.get( 'order' ),
                    folder:     id,
                    screen:     view.model.get( 'screen' ),
                    items_per_page: view.model.get( 'itemsPerPage' )
                }, function( content ){
                    folderState.set( 'content', content );
                    folderState.set( 'needsUpdate', false );
                    view.renderFolderContent();
                    view.model.set( 'loading', false );
                } );
                */
            } else {
                view.renderFolderContent();
            }

        },

        folderParentChanged: function( folder ){

            folder.save();

            this.renderFolderPath();
            this.renderFolderTree();

        },

        render: function() {

            var view = this;

            //this.$el.html( _.template( $( '#tmpl-wicked-folder-browser' ).html() ) );

            this.renderActions();
            this.renderFolderPath();
            this.renderFolderTree();
            this.renderFolderContent();
            this.renderFolderDialog();

            this.$( '.wicked-folder-tree-pane' ).resizable( {
                resizeHeight:   false,
                handles:        'e',
                minWidth:       200,
                containment:    this.$el,
                stop:           function() {
                    var width = view.$( '.wicked-folder-tree-pane' ).width();
                    view.model.set( 'treePaneWidth', width );
                }
            } );

        },

        renderFolderContent: function(){

            var view = this;

            this.$( '.wicked-folder-contents-pane' ).html( this.folderState().get( 'content' ) );

            // Move pagination
            this.$( '.wicked-head .tablenav' ).remove();
            this.$( '.wicked-foot .tablenav' ).remove();

            this.$( '.wicked-folder-contents-pane .tablenav.top' ).appendTo( this.$( '.wicked-head .wicked-lower-row .wicked-right' ) );
            this.$( '.wicked-folder-contents-pane .tablenav.bottom' ).appendTo( this.$( '.wicked-foot .wicked-right' ) );

            // Preserve hidden columns
            $( '#screen-meta #adv-settings [type="checkbox"]' ).each( function( index, item ){
                var column = $( item ).val();
                if ( $( item ).prop( 'checked' ) ) {
                    view.$( '.wicked-folder-contents-pane .column-' + column ).removeClass( 'hidden' );
                } else {
                    view.$( '.wicked-folder-contents-pane .column-' + column ).addClass( 'hidden' );
                }
            } );

            /*
            this.$( '.wicked-folder-contents-pane tr[id^="post-"]' ).draggable( {
                revert:         'invalid',
                helper:         'clone',
                containment:    '#wicked-folder-browser .wicked-panes'
            } );
            */

            this.$( '.wicked-folder-contents-pane .wicked-move-multiple' ).draggable( {
                revert:         'invalid',
                helper:         'clone',
                containment:    '#wicked-folder-browser .wicked-panes',
                appendTo:       '#wicked-folder-browser'
            } );

            this.updateFolderStateSelections();

        },

        renderFolderTree: function(){

            var view = this;

            // Use setElement so that events are re-bound
            this.folderTreeView.setElement( this.$( '.wicked-folder-tree' ) ).render();

            this.$( '.wicked-folder-tree-pane' ).width( this.model.get( 'treePaneWidth' ) );

            this.$( '.wicked-folder-leaf.wicked-movable' ).not( '[data-folder-id="0"]' ).draggable( {
                revert: 'invalid',
                helper: 'clone'
            } );

            this.$( '.wicked-post-leaf' ).draggable( {
                revert: 'invalid',
                helper: 'clone'
            } );

            this.$( '.wicked-tree' ).wicked_stick_in_parent( {
                offset_top: 34
            } );

            this.$( '.wicked-tree [data-folder-id="0"] .wicked-folder' ).droppable( {
                hoverClass: 'wicked-drop-hover',
                accept: function( draggable ){

                    var destinationFolderId = $( this ).parents( 'li' ).eq( 0 ).attr( 'data-folder-id' ),
                        folder = view.folder(),
                        accept = false;

                    if ( draggable.hasClass( 'wicked-folder' ) || draggable.hasClass( 'wicked-folder-leaf' ) || draggable.hasClass( 'wicked-post-leaf' ) || draggable.hasClass( 'wicked-move-multiple' ) || ( draggable.is( 'tr' ) && -1 != draggable.attr( 'id' ).indexOf( 'post-' ) ) ) {
                        accept = true;
                    }

                    if ( draggable.is( 'tr' ) && -1 != draggable.attr( 'id' ).indexOf( 'post-' ) ) {
                        // Don't allow posts to be moved to the folder they're already in
                        if ( destinationFolderId == folder.id ) {
                            accept = false;
                        }
                        // For now, don't allow posts to be dragged to 'all folders'
                        if ( destinationFolderId == 0 ) {
                            accept = false;
                        }
                    }

                    if ( draggable.hasClass( 'wicked-folder-leaf' ) ) {
                        var parent = draggable.parents( 'li' ).eq( 0 ).attr( 'data-folder-id' );
                        // Don't allow folders to be moved to the folder they're already in
                        if ( destinationFolderId == parent ) {
                            accept = false;
                        }
                    }

                    if ( draggable.hasClass( 'wicked-post-leaf' ) ) {
                        var parent = draggable.parents( 'li' ).eq( 0 ).attr( 'data-folder-id' );
                        // Don't allow posts to be moved to the folder they're already in
                        if ( destinationFolderId == parent ) {
                            accept = false;
                        }
                        // For now, don't allow posts to be dragged to 'all folders'
                        if ( destinationFolderId == 0 ) {
                            accept = false;
                        }
                    }

                    if ( draggable.hasClass( 'wicked-move-multiple' ) ) {
                        // Don't allow posts to be moved to the folder they're already in
                        if ( destinationFolderId == folder.id ) {
                            accept = false;
                        }
                        if ( destinationFolderId == 0 ) {
                            accept = false;
                        }
                    }

                    return accept;

                },
                tolerance: 'pointer',
                drop: function( e, ui ) {

                    // TODO: clean this up

                    var destinationFolderId = $( this ).parents( 'li' ).eq( 0 ).attr( 'data-folder-id' );

                    if ( ui.draggable.is( 'tr' ) && -1 != ui.draggable.attr( 'id' ).indexOf( 'post-' ) ) {

                        var objectId = $( ui.draggable ).attr( 'id' ).substring( 5 );

                        view.model.moveObject( 'post', objectId, destinationFolderId, view.folder().id );
                        view.folderStates.get( view.folder().id ).set( 'needsUpdate', true );
                        view.folderStates.get( destinationFolderId ).set( 'needsUpdate', true );
                        view.removePostRows( objectId );

                    } else if ( ui.draggable.hasClass( 'wicked-post-leaf' ) ) {

                        var objectId    = $( ui.draggable ).attr( 'data-post-id' );
                        sourceFolderId  = $( ui.draggable ).parents( 'li' ).eq( 0 ).attr( 'data-folder-id' );

                        view.model.moveObject( 'post', objectId, destinationFolderId, sourceFolderId );
                        view.folderStates.get( sourceFolderId ).set( 'needsUpdate', true );
                        view.folderStates.get( destinationFolderId ).set( 'needsUpdate', true );
                        view.removePostRows( objectId );

                        var destinationFolderPosts = view.folders().get( destinationFolderId ).get( 'posts' ),
                            sourceFolderPosts = view.folders().get( sourceFolderId ).get( 'posts' ),
                            post = sourceFolderPosts.get( objectId );

                        sourceFolderPosts.remove( post );
                        destinationFolderPosts.add( post );


                    } else if ( ui.draggable.hasClass( 'wicked-move-multiple' ) ) {

                        var objectIds = [];

                        ui.draggable.find( '.wicked-items div' ).each( function( index, item ){
                            objectIds.push( $( item ).attr( 'data-object-id' ) );
                        });

                        view.model.moveObject( 'post', objectIds, destinationFolderId, view.folder().id );
                        view.folderStates.get( view.folder().id ).set( 'needsUpdate', true );
                        view.folderStates.get( destinationFolderId ).set( 'needsUpdate', true );
                        view.removePostRows( objectIds );

                    } else {

                        var objectId = $( ui.draggable ).attr( 'data-folder-id' );
                        view.folders().get( objectId ).set( 'parent', destinationFolderId );

                    }

                }
            });

        },

        renderFolderPath: function(){

            var controller = this.model,
                folders = this.model.get( 'folders' ),
                folder = this.model.get( 'folder' );

            var folderPath = new wickedfolders.views.FolderPath({
                collection: folders,
                selected:   folder.id
            });

            folderPath.render();

            this.$( '.wicked-folder-path-pane' ).html( folderPath.el );

        },

        renderFolderDialog: function(){

            this.folderDialogView.setElement( $( '#wicked-folder-dialog-container') ).render();

        },

        renderActions: function(){

            var type = this.folder().get( 'type' );

            this.$( '.wicked-folder-browser-actions' ).empty();

            jQuery( '#wp-admin-bar-wicked-folders-edit-folder' ).hide();
            jQuery( '#wp-admin-bar-wicked-folders-delete-folder' ).hide();

            var ul = $( '<ul class="subsubsub">' );
            ul.append( '<li><a class="wicked-add-new-folder" href="#">' + wickedFoldersL10n.addNewFolderLink + '</a>|</li>' );

            if ( '0' != this.folder().id && 'Wicked_Folders_Term_Folder' == type ) {
                ul.append( '<li><a class="wicked-edit-folder" href="#">' + wickedFoldersL10n.editFolderLink + '</a>|</li>' );
                ul.append( '<li><a class="wicked-delete-folder" href="#">' + wickedFoldersL10n.deleteFolderLink + '</a>|</li>' );

                jQuery( '#wp-admin-bar-wicked-folders-edit-folder' ).show();
                jQuery( '#wp-admin-bar-wicked-folders-delete-folder' ).show();

            }

            ul.append( '<li><a class="wicked-expand-all" href="#">' + wickedFoldersL10n.expandAllFoldersLink + '</a>|</li>' );
            ul.append( '<li><a class="wicked-collapse-all" href="#">' + wickedFoldersL10n.collapseAllFoldersLink + '</a></li>' );

            if ( '0' == this.folder().id ) {
                ul.append( '<li>| <input id="wicked-hide-assigned" name="hide_assigned" type="checkbox" value="1" /><label for="wicked-hide-assigned">' + wickedFoldersL10n.hideAssignedItems + '</label> <span class="dashicons dashicons-editor-help" title="' + wickedFoldersL10n.hideAssignedItemsTooltip + '"></span></li>' );
            }

            this.$( '.wicked-folder-browser-actions' ).append( ul );

            this.$( '#wicked-hide-assigned' ).prop( 'checked', this.model.get( 'hideAssignedItems' ) );

        },

        addNewFolder: function( e ){

            e.preventDefault();

            this.folderDialogView.options.mode = 'add';
            this.folderDialogView.setFolder( new wickedfolders.models.Folder({
                parent:     this.folder().id,
                postType:   this.model.get( 'postType' ),
                taxonomy:   this.model.get( 'taxonomy' )
            }) );
            this.folderDialogView.render();
            this.folderDialogView.open();

        },

        editFolder: function( e ){

            e.preventDefault();

            this.folderDialogView.options.mode = 'edit';
            this.folderDialogView.setFolder( this.folder() );
            this.folderDialogView.render();
            this.folderDialogView.open();

        },

        deleteFolder: function( e ){

            e.preventDefault();

            this.folderDialogView.options.mode = 'delete';
            this.folderDialogView.setFolder( this.folder() );
            this.folderDialogView.render();
            this.folderDialogView.open();

        },

        expandAllFolders: function( e ){

            e.preventDefault();

            var ids = this.folders().pluck( 'id' );

            this.folderTreeView.model.set( 'expanded', ids );

        },

        collapseAllFolders: function( e ){

            e.preventDefault();

            this.folderTreeView.model.set( 'expanded', [ '0' ] );

        },

        setLoadingMask: function(){
            if ( this.model.get( 'loading' ) ) {
                this.$( '.wicked-body' ).addClass( 'wicked-loading-mask' );
            } else {
                this.$( '.wicked-body' ).removeClass( 'wicked-loading-mask' );
            }
        },

        removePostRows: function( postId ){

            var view = this,
                ids = _.isArray( postId ) ? postId : [ postId ];

            _.each( ids, function( id ){
                view.$( '.wicked-folder-contents-pane tr[id="post-' + id + '"]' ).fadeOut( 500, function(){
                    $( this ).remove();
                });
            } );

        },

        formSubmitted: function( e ) {

            // Remove inputs like _wpnonce and _wp_http_referer
            this.$( 'input[name^="_"]' ).remove();

        }

    });

})( window, jQuery, undefined );
