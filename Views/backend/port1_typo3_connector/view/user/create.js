//{block name="backend/user_manager/view/user/create" append}
Ext.define('Shopware.apps.SwagExtendUserManager.view.user.Create', {
    override:'Shopware.apps.UserManager.view.user.Create',

    getApiFieldset: function () {
        var me = this,
            fieldset = me.callParent(arguments);

        // me.pxShopwareTypo3ApiUrlField = Ext.create('Ext.form.field.Text', {
        //     name:'TYPO3 API-URL',
        //     labelWidth: 100,
        //     flex: 1,
        //     allowBlank: false,
        //     style: {
        //         width: '100%'
        //     },
        //     width: 575,
        //     supportText: "{s name=create_user/typo3_api_url_support_text}Enter the TYPO3 API-URL here to push notifications about changes of articles / categories to this endpoint.{/s}",
        //     fieldLabel: '{s name=create_user/typo3_api_url}TYPO3-API-URL{/s}'
        // });

        me.typo3ApiUrlField = Ext.create('Ext.form.field.Text', {
            xtype: 'textfield',
            name: 'typo3_api_url',
            labelWidth: 100,
            width: 575,
            fieldLabel: 'TYPO3-API-URL'
        });


        var container = Ext.create('Ext.container.Container', {
            // Implementiert das Column Layout
            xtype: 'container',
            layout: 'column',
            style: {
                padding: '10px'
            },
            items:[ me.typo3ApiUrlField ]
        });

        fieldset.add(container);

        return fieldset;
    }

    // onStoresLoaded: function() {
        // var me = this;
        //
        // me.callParent(arguments);

        // alert('foo');


        // Ext.Ajax.request({
        //     url: '{url controller=AttributeData action=loadData}',
        //     params: {
        //         _foreignKey: me.record.get('mainDetailId'),
        //         _table: 's_articles_attributes'
        //     },
        //     success: function(responseData, request) {
        //         var response = Ext.JSON.decode(responseData.responseText);
        //
        //         me.typo3ApiUrlField.setValue(response.data['__attribute_typo3_api_url']);
        //     }
        // });
    // }
});
//{/block}