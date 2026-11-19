<?php
/**
 * Add an end user and a manager, move an extension, then remove the end user.
 *
 *   IC_ADMIN_EMAIL=… IC_ADMIN_PASSWORD=… COMPANY_ID=… php examples/manage_users.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use IntelligenceCloud\Client;
use IntelligenceCloud\Exception\ExtensionMappingError;
use IntelligenceCloud\Lifecycle\UserSpec;

$ic = new Client(email: getenv('IC_ADMIN_EMAIL'), password: getenv('IC_ADMIN_PASSWORD'));
$customer = $ic->workflows->getSeatCustomer(getenv('COMPANY_ID'));

try {
    $sam = $ic->workflows->addFrontlineUser($customer, new UserSpec('sam.ortiz@example.com', 'Sam', 'Ortiz'), '2101');
} catch (ExtensionMappingError $e) {
    // The user exists but the number was refused: connector not active, or the extension is
    // monitored at another location. No invitation has been sent. Fix it and run again: the re-run
    // finishes the setup and then emails the invitation.
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

// The first manager switches both alerts on; the invitation is emailed last. The manager reads
// every end-user line; their own phone line is not captured on the Seat.
$alex = $ic->workflows->addManager($customer, new UserSpec('alex.kim@example.com', 'Alex', 'Kim'));
echo 'alerts switched on: ', count($alex->alertsActivated), "\n";
$ic->workflows->changeUserExtension($customer, $sam->userId, '2101', '2106');

foreach ($ic->workflows->listFrontlineUsers($customer) as $u) {
    echo "end user: {$u['email']}\n";
}
foreach ($ic->workflows->listManagers($customer) as $u) {
    echo "manager:  {$u['email']}\n";
}

// Preview drift between the end users' lines and what managers, First Alert and Insights Hub hold.
print_r($ic->workflows->syncNumbers($customer, dryRun: true));

$ic->workflows->removeUser($customer, $sam->userId);
