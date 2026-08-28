<?php
/** Country badge tests use only an in-memory SQLite database. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../vendor/autoload.php';

use App\Support\OfferCountryBadges;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\Facade;

$checks = 0;
function verify($condition, $message) {
    global $checks;
    if (!$condition) throw new RuntimeException($message);
    $checks++;
}
$example = 'BE - v3 - AU/CA/DK/ES/FI/FR/GB/IR/IT/NO/NZ/SE/US (MJ)';
$info = OfferCountryBadges::present($example);
verify($info['name'] === 'BE - v3 (MJ)', 'Country run not removed cleanly');
verify(count($info['countries']) === 13, 'Expected all thirteen country badges');
verify($info['source'] === 'title', 'Title fallback source missing');
verify(isset($info['countries']['IR']) && !isset($info['countries']['IE']), 'IR must not silently become IE');
$info = OfferCountryBadges::present('Example - US / UK / GB / US Only - v2');
verify(array_keys($info['countries']) === ['US', 'GB'], 'Aliases or duplicate codes not normalized');
verify($info['name'] === 'Example - v2', 'Leftover separators or Only text');
$cherryTitle = 'Cherry TV - T1/T2 - US/UK/CA/AU/AT/BE/DE/FI/FR/DE/IS/IE/IT/LU/NL/NZ/NO/SE/CH (5)';
$info = OfferCountryBadges::present($cherryTitle);
verify($info['name'] === 'Cherry TV - T1/T2 (5)', 'Tier labels must survive country cleanup');
verify(count($info['countries']) === 18, 'Cherry TV countries missing or duplicated');
$info = OfferCountryBadges::present('Chick Tok - US, CA, AU, UK (3a)');
verify($info['name'] === 'Chick Tok (3a)', 'Comma list not removed from display title');
verify(array_keys($info['countries']) === ['US', 'CA', 'AU', 'GB'], 'Comma list badges incorrect');
$info = OfferCountryBadges::present('Example - US,CA/AU, UK Only - v2');
verify($info['name'] === 'Example - v2' && count($info['countries']) === 4, 'Mixed separators not supported');
foreach (['Example - US, ZZ, CA', 'Example - Except US, CA', 'Example - US, CA blocked'] as $name) {
    $info = OfferCountryBadges::present($name);
    verify($info['name'] === $name && !$info['countries'], 'Unsafe comma list was extracted');
}
foreach (['MC ONLY', 'BE - v3', 'Offer - US/ZZ/CA', 'Offer - Except US/CA', 'Offer - No US/CA', 'Offer - US/CA blocked', 'Offer - All GEOs'] as $name) {
    $info = OfferCountryBadges::present($name);
    verify($info['name'] === $name && !$info['countries'], 'Ambiguous title was misrepresented: ' . $name);
}
$allow = [['idrule' => 1, 'deny' => 0, 'country_code' => 'AU'], ['idrule' => 1, 'deny' => 0, 'country_code' => 'NZ']];
$info = OfferCountryBadges::present('Example - US/CA (3)', $allow);
verify(array_keys($info['countries']) === ['AU', 'NZ'] && $info['source'] === 'rules', 'Active rules must override title countries');
verify($info['name'] === 'Example (3)', 'Rule-based display name not cleaned');
$info = OfferCountryBadges::present('Example - US/CA', [['idrule' => 1, 'deny' => 1, 'country_code' => 'CA']]);
verify($info['mode'] === 'excluded' && $info['name'] === 'Example - US/CA', 'Blocked countries presented as allowed');
$multipleRules = array_merge($allow, [['idrule' => 2, 'deny' => 1, 'country_code' => 'AG']]);
$info = OfferCountryBadges::present($cherryTitle, $multipleRules);
verify($info['source'] === 'title' && $info['note'] === 'Multiple GEO rules' && $info['mode'] === 'listed', 'Multiple rules need an explicit title-source warning');
verify($info['name'] === 'Cherry TV - T1/T2 (5)' && count($info['countries']) === 18, 'Cherry TV fallback failed with active rules');
verify(!isset($info['countries']['AG']), 'Rule countries must not be merged into title countries');
$info = OfferCountryBadges::present('Example without a country list', $multipleRules);
verify(!$info['countries'] && $info['note'] === 'Multiple GEO rules', 'Multiple rules without a title list must remain uncombined');
$info = OfferCountryBadges::present('Example - US/CA', [['idrule' => 1, 'deny' => 0, 'country_code' => null]]);
verify(!$info['countries'] && $info['note'] === 'Custom GEO rule', 'Empty active rule incorrectly fell back to title');

$capsule = new Manager();
$capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
Facade::setFacadeApplication($capsule->getContainer());
$capsule->getContainer()->instance('db', $capsule->getDatabaseManager());
$db = $capsule->getConnection();
$db->statement('CREATE TABLE rule (idrule INTEGER, offer_idoffer INTEGER, type TEXT, is_active INTEGER, deny INTEGER)');
$db->statement('CREATE TABLE geo_rule (idgeo_rule INTEGER, rule_idrule INTEGER)');
$db->statement('CREATE TABLE country_list (geo_rule_idgeo_rule INTEGER, country_code TEXT)');
$db->table('rule')->insert([
    ['idrule' => 1, 'offer_idoffer' => 10, 'type' => 'geo', 'is_active' => 1, 'deny' => 0],
    ['idrule' => 2, 'offer_idoffer' => 11, 'type' => 'geo', 'is_active' => 0, 'deny' => 0],
    ['idrule' => 3, 'offer_idoffer' => 99, 'type' => 'geo', 'is_active' => 1, 'deny' => 0],
    ['idrule' => 4, 'offer_idoffer' => 12, 'type' => 'geo', 'is_active' => 1, 'deny' => 1],
]);
$db->table('geo_rule')->insert([
    ['idgeo_rule' => 1, 'rule_idrule' => 1], ['idgeo_rule' => 2, 'rule_idrule' => 2],
    ['idgeo_rule' => 3, 'rule_idrule' => 3], ['idgeo_rule' => 4, 'rule_idrule' => 4],
]);
$db->table('country_list')->insert([
    ['geo_rule_idgeo_rule' => 1, 'country_code' => 'AU'], ['geo_rule_idgeo_rule' => 1, 'country_code' => 'AU'],
    ['geo_rule_idgeo_rule' => 2, 'country_code' => 'FR'], ['geo_rule_idgeo_rule' => 3, 'country_code' => 'DE'],
    ['geo_rule_idgeo_rule' => 4, 'country_code' => 'CA'],
]);
$offers = collect([
    (object) ['idoffer' => 10, 'offer_name' => 'Sample - US/CA'],
    (object) ['idoffer' => 11, 'offer_name' => 'Fallback - US/CA'],
    (object) ['idoffer' => 12, 'offer_name' => 'Restricted'],
]);
$db->enableQueryLog();
$info = OfferCountryBadges::forOffers($offers);
verify(count($db->getQueryLog()) === 1, 'Country badges added per-offer queries');
verify(array_keys($info) === [10, 11, 12], 'Countries leaked from outside authorized inventory');
verify(array_keys($info[10]['countries']) === ['AU'], 'Rule countries were not deduplicated');
verify($info[11]['source'] === 'title' && array_keys($info[11]['countries']) === ['US', 'CA'], 'Inactive rule was used');
verify($info[12]['mode'] === 'excluded', 'Deny flag was lost in query');
verify($offers[0]->offer_name === 'Sample - US/CA', 'Saved offer name was mutated');
$db->flushQueryLog();
verify(OfferCountryBadges::forOffers([]) === [] && !$db->getQueryLog(), 'Empty inventory should not query');
$db->disconnect();
echo "Passed {$checks} country badge assertions (isolated SQLite).\n";
