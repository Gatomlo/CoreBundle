/*
 * This file is part of the Claroline Connect package.
 *
 * (c) Claroline Consortium <consortium@claroline.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

(function () {
    'use strict';
    
    $('body').on('click', '.user-picker', function () {
        var multiple = $(this).data('multiple');
        var showAllUsers = $(this).data('show-all-users');
        var showUsername = $(this).data('show-username');
        var showMail = $(this).data('show-mail');
        var showCode = $(this).data('show-code');
        
        var userIdsTxt = '' + $(this).data('excluded-users');
        userIdsTxt = userIdsTxt.trim();
        var userIds = userIdsTxt !== '' ?
            userIdsTxt.split(';') :
            [];
    
        var groupIdsTxt = '' + $(this).data('forced-groups');
        groupIdsTxt = groupIdsTxt.trim();
        var forcedGroupIds = groupIdsTxt !== '' ?
            groupIdsTxt.split(';') :
            [];
    
        var roleIdsTxt = '' + $(this).data('forced-roles');
        roleIdsTxt = roleIdsTxt.trim();
        var forcedRoleIds = roleIdsTxt !== '' ?
            roleIdsTxt.split(';') :
            [];
    
        var workspaceIdsTxt = '' + $(this).data('forced-workspaces');
        workspaceIdsTxt = workspaceIdsTxt.trim();
        var forcedWorkspaceIds = workspaceIdsTxt !== '' ?
            workspaceIdsTxt.split(';') :
            [];
    
        var parameters = {};
        parameters.excludedUserIds = userIds;
        parameters.forcedGroupIds = forcedGroupIds;
        parameters.forcedRoleIds = forcedRoleIds;
        parameters.forcedWorkspaceIds = forcedWorkspaceIds;
        
        if (multiple === undefined) {
            multiple = 'multiple';
        }
        var route = Routing.generate(
            'claro_user_picker',
            {
                'mode': multiple,
                'showAllUsers': showAllUsers,
                'showUsername': showUsername,
                'showMail': showMail,
                'showCode': showCode
            }
        );
        route += '?' + $.param(parameters);
        
        window.Claroline.Modal.fromUrl(
            route,
            null
        );
    });
})();