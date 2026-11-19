<?php
/**
 * Provision a Base Intelligence Seat customer with admin credentials.
 *
 *   IC_ADMIN_EMAIL=… IC_ADMIN_PASSWORD=… NS_API_URL=… NS_DOMAIN=… NS_API_KEY=… php examples/provision_seat_customer.php
 *
 * Customer Threat phrases are provided by your Tresic contact: put them in threat-phrases.json
 * (a JSON list of strings) next to this script.
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use IntelligenceCloud\Client;
use IntelligenceCloud\Lifecycle\ConnectorSpec;
use IntelligenceCloud\Lifecycle\LocationSpec;
use IntelligenceCloud\Lifecycle\SeatCustomerSpec;

$ic = new Client(email: getenv('IC_ADMIN_EMAIL'), password: getenv('IC_ADMIN_PASSWORD'), userAgent: 'harborview-provisioning/1.0');

$phrases = json_decode((string) @file_get_contents(__DIR__ . '/threat-phrases.json'), true) ?: null;

$customer = $ic->workflows->provisionSeatCustomer(new SeatCustomerSpec(
    companyName: 'Riverbend Auto Group',
    adminName: 'Jordan Lee',
    adminEmail: 'jordan.lee@example.com',
    adminPhone: '+15555550142',
    location: new LocationSpec('Riverbend - Main', '100 River Rd', 'Springfield', 'IL', 'United States', '62701', 'csps-msps'),
    threatPhrases: $phrases,
    connector: new ConnectorSpec(getenv('NS_API_URL'), getenv('NS_DOMAIN'), getenv('NS_API_KEY')),
    ssoDomain: null, // e.g. 'riverbendauto.example.com' to get the DNS TXT record
    companyId: getenv('IC_COMPANY_ID') ?: null, // store the returned companyId and pass it on every re-run
));

echo json_encode($customer->toArray(), JSON_PRETTY_PRINT), "\n";
// Both alerts are created switched off. Add end users, then managers (examples/manage_users.php):
// the first manager switches the alerts on. Each user is invited once their lines are mapped.
if ($customer->connectorStatus === 'error') {
    fwrite(STDERR, "The connector is in error ({$customer->connectorStateReason}). Use a read/write key and run this again: the key is applied and the connector restarted.\n");
}
