<?php

return [
    'invoices_issuing_types' => [  // for zatca phase 2
        '1100',  // 1100 for together
        '0100',  // 0100 for simplified
        '1000'  // 1000 for standard
    ],
    'zatca_stages' => [
        'developer-portal',
        'simulation',
        'core',
    ],
    'permissions_modules' => [
        'users',
        'user_permissions',
        'branches',
        'items',
        'initial_quantities',
        'stock_entries',
        'tax_invoices',
        'simplified_tax_invoices',
        'purchase_invoices',
        'sales_returns',
        'expense_vouchers',
        'receipt_vouchers',
        'inventory_reports',
        'warehouses',
        'customers',
        'suppliers',
        'accounts',
        'system_settings',
        'gold_prices',
        'stock',
        'workbook',
        'breakbook',
        'convert_work_to_break',
        'cash_in_entries',
        'cash_out_entries',
        'inventory_list',
        'accounting_reports',
        'journal_entries',
        'gold_balance_sheet',
        'stock_settlements'
    ],
    'accounts_types' => [
        'not_have',
        'assets',
        'equity',
        'liabilities',
        'revenues',
        'expenses',
    ],
    'transfers_sides' => [
        'not_have',
        'budget',
        'income_statement',
    ],
    'accounts_categories' => [
        'parent',
        'child',
    ],
    'purchase_types' => [
        'normal',
        'discount_from_scrap',
        'discount_from_pure',
    ]
];
