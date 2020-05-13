define(['mage/storage'], function (storage) {
    'use strict';
    return {
        getlist: function () {
            return storage.get('rest/V1/customer/todo/tasks');
        }
    };
});
