ip_in_ranges( '12.34.56.78', '12.34.56.0/24', '12.34.0.0/16' ) === true &
ip_in_ranges( '12.34.56.78', '65.43.0.0/16', '12.34.56.78/32' ) === true &
ip_in_ranges( '12.34.56.78', '12.0.0.0/8', '13.0.0.0/8' ) === true &
ip_in_ranges( '12.34.56.78', '12.34.56.78', '2001:db8::/16' ) === true &
ip_in_ranges( '12.34.56.78', '12.1.2.255/8', '::' ) === true &
ip_in_ranges( '1.1.1.1', '1.1.1.1/32', '2.2.2.2/32' ) === true &
ip_in_ranges( '1.1.1.1', '1.1.1.1', '1.1.1.1', '1.1.1.1/32' ) === true &
ip_in_ranges( '1.1.1.1', '0.0.0.0/0', '0.0.0.0/1', '0.0.0.0/2', '0.0.0.0/3' ) === true &
ip_in_ranges( '1.1.1.1', '::', '::/0', '::/1' ) === false &
ip_in_ranges( '123.123.123.123', '123.123.123.123', '123.123.123.123' ) === true &
ip_in_ranges( '123.123.123.123', '123.0.0.0-123.122.0.0', '123.124.0.0-124.122.0.0' ) === false &
ip_in_ranges( '123.123.123.123', '125.0.0.0 - 127.0.0.0', '123.0.0.0-124.0.0.0' ) === true &
ip_in_ranges( '123.123.123.123', '123.0.0.0-127.0.0.0', '123.123.0.0 - 124.0.0.0' ) === true &
ip_in_ranges( '123.123.123.123', '127.0.0.0-123.0.0.0', '120.0.0.0-123.0.0.0' ) === false &
ip_in_ranges( '123.123.123.123', '127.0.0.0-123.0.0.0', '123.0.0.0-127.0.0.0' ) === true &
ip_in_ranges( '11.11.11.11', '11.11.11.1', '1.11.11.11', '11.1.11.11', '11.11.1.11' ) === false &
ip_in_ranges( '1.1.1.1', '::-ffff::', '0.0.0.0-255.255.255.255' ) === true &
ip_in_ranges( '2001:db8:85a3::8a2e:0370:7334', '::-ffff::', '0.0.0.0-255.255.255.255' ) === true &
ip_in_ranges( '2001:db8:85a3::8a2e:0370:7334', '2001:db8:85a3::8a2e:370:7334/113', '2001:db8:85a3::8a2e:370:0000-2001:db8:85a3::8a2e:370:8888' ) === true &
ip_in_ranges( '1.1.1.1', '::-ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff', '0.0.0.0' ) === false &
ip_in_ranges( '2001:db8:85a3::8a2e:370:0000', '0.0.0.0-255.255.255.255', '::' ) === false
