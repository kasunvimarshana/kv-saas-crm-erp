<?php

return [
    'name' => 'IAM',
    
    /*
    |--------------------------------------------------------------------------
    | Default Permissions
    |--------------------------------------------------------------------------
    |
    | Define default permissions that should be created when the module is installed
    |
    */
    'default_permissions' => [
        // Permission management
        'create-permission' => 'Create permissions',
        'update-permission' => 'Update permissions',
        'delete-permission' => 'Delete permissions',
        'view-permission' => 'View permissions',
        'assign-permissions' => 'Assign permissions to roles and users',
        
        // Role management
        'create-role' => 'Create roles',
        'update-role' => 'Update roles',
        'delete-role' => 'Delete roles',
        'view-role' => 'View roles',
        'assign-roles' => 'Assign roles to users',
        
        // Group management
        'create-group' => 'Create groups',
        'update-group' => 'Update groups',
        'delete-group' => 'Delete groups',
        'view-group' => 'View groups',
        'manage-group-members' => 'Manage group members',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Categories
    |--------------------------------------------------------------------------
    */
    'permission_categories' => [
        'iam' => 'Identity and Access Management',
        'sales' => 'Sales & CRM',
        'inventory' => 'Inventory Management',
        'accounting' => 'Accounting & Finance',
        'hr' => 'Human Resources',
        'procurement' => 'Procurement',
    ],
];
