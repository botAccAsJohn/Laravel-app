<?php

test('format_price formats numbers correctly', function () {
    $formatted = format_price(100);
    expect($formatted)->toBeString();
    expect($formatted)->toContain('100');
});

test('order_status_badge returns correct CSS classes', function () {
    expect(order_status_badge('pending'))->toBe('badge bg-warning text-dark');
    expect(order_status_badge('paid'))->toBe('badge bg-info text-dark');
    expect(order_status_badge('shipped'))->toBe('badge bg-primary');
    expect(order_status_badge('delivered'))->toBe('badge bg-success');
    expect(order_status_badge('unknown'))->toBe('badge bg-secondary');
});

test('human_file_size formats byte sizes correctly', function () {
    expect(human_file_size(500))->toBe('500 B');
    expect(human_file_size(1024))->toBe('1.00 KB');
    expect(human_file_size(1048576))->toBe('1.00 MB');
    expect(human_file_size(1073741824))->toBe('1.00 GB');
});
