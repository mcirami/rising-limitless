<?php
namespace App\Http\Controllers;

use App\Company;
use App\Support\NetworkTheme;
use Illuminate\Http\Request;
use LeadMax\TrackYourStats\System\Company as LegacyCompany;

class NetworkSettingsController extends Controller
{
    public function show()
    {
        $company = Company::instance()->first();
        return view('settings.network', ['company' => $company, 'colors' => NetworkTheme::colors($company->colors ?? ''), 'pageTitle' => 'Network settings']);
    }

    public function save(Request $request)
    {
        $company = Company::instance()->firstOrFail();
        $data = $request->validate(self::rules());
        $company->shortHand = $data['shortHand'];
        foreach (['email', 'skype', 'login_url', 'landing_page'] as $key) {
            $company->$key = $data[$key] ?? '';
        }
        $company->colors = implode(';', NetworkTheme::colors(array_map(fn($i) => $data['valueSpan'.$i], range(1, 11))));
        try {
            $company->saveOrFail();
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['settings' => 'Settings could not be saved. Please try again.']);
        }
        LegacyCompany::loadFromSession()->reloadSettings();
        return redirect('/settings.php')->with('settings_saved', 'Network settings saved.');
    }

    public static function rules(): array
    {
        $address = ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
            $url = preg_match('~^https?://~i', $value) ? $value : 'https://'.$value;
            if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($url, PHP_URL_SCHEME) ?? ''), ['http','https']) || parse_url($url, PHP_URL_USER) !== null) {
                $fail('Enter a valid HTTP(S) address or hostname.');
            }
        }];
        $rules = ['shortHand' => 'required|string|max:100', 'email' => 'nullable|email|max:255', 'skype' => 'nullable|string|max:100', 'login_url' => $address, 'landing_page' => $address];
        foreach (range(1,11) as $i) $rules['valueSpan'.$i] = ['required', 'string', 'regex:/^#?[a-fA-F0-9]{6}$/D'];
        return $rules;
    }

    public function upload(Request $request, string $kind)
    {
        Company::instance()->firstOrFail();
        $logo = $kind === 'logo';
        $key = $logo ? 'file1' : 'file2';
        $request->validate([$key => ['required', 'file', 'max:2048']]);
        $file = $request->file($key);
        $bytes = file_get_contents($file->getRealPath());
        $valid = $logo ? (@getimagesize($file->getRealPath())[2] ?? null) === IMAGETYPE_PNG
            : strlen($bytes) >= 22 && substr($bytes,0,4) === "\x00\x00\x01\x00" && unpack('v', substr($bytes,4,2))[1] > 0;
        if ($valid && !$logo) {
            $count = unpack('v', substr($bytes,4,2))[1];
            $valid = strlen($bytes) >= 6 + 16 * $count;
            for ($i = 0; $valid && $i < $count; $i++) {
                $entry = unpack('Vsize/Voffset', substr($bytes, 6 + 16 * $i + 8, 8));
                $valid = $entry['size'] > 0 && $entry['offset'] >= 6 + 16 * $count
                    && $entry['offset'] + $entry['size'] <= strlen($bytes);
            }
        }
        if (!$valid) return back()->withErrors(['upload' => $logo ? 'Choose a valid PNG image.' : 'Choose a valid ICO icon.']);
        $sub = LegacyCompany::getCustomSub();
        abort_unless(preg_match('/^[a-zA-Z0-9_-]+$/D', $sub), 422);
        $directory = public_path('images/'.$sub);
        // Validate before replacing an existing asset; rename keeps replacement atomic.
        $temporary = null;
        try {
            if (!is_dir($directory) && !mkdir($directory,0755,true)) throw new \RuntimeException();
            $temporary = tempnam($directory, 'brand-');
            if (!$temporary) throw new \RuntimeException();
            $file->move($directory, basename($temporary));
            chmod($temporary, 0644);
            if (!rename($temporary, $directory.'/'.($logo ? 'logo.png' : 'favicon.ico'))) throw new \RuntimeException();
        } catch (\Throwable $e) {
            if ($temporary && is_file($temporary)) unlink($temporary);
            return back()->withErrors(['upload' => 'Upload failed; please check the image directory permissions.']);
        }
        return redirect('/settings.php')->with('settings_saved', ($logo ? 'Logo' : 'Favicon').' uploaded.');
    }
}
