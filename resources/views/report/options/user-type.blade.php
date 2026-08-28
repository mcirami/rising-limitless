<script type="text/javascript">
    function handleSelect(elm) {
        var url = new URL(window.location.href);
        url.searchParams.set('role', elm.value);
        window.location.assign(url.toString());
    }
</script>

<select onchange="handleSelect(this);" class="selectBox " id="role" name="role" aria-label="Filter by account type">


    @if(\LeadMax\TrackYourStats\System\Session::userType() == \App\Privilege::ROLE_GOD)
        <option @if(request('role',3) == 1) selected @endif value='1'>Admins
        </option>
    @endif


    @if(\LeadMax\TrackYourStats\System\Session::permissions()->can("create_managers"))
        <option @if(request('role',3) == 2) selected @endif value='2'>{{ env('ACCOUNT_TYPE_TEXT') ? env('ACCOUNT_TYPE_TEXT') . 's' : 'Managers' }}</option>
    @endif
    <option @if(request('role',3 ) == 3) selected @endif value='3'>Agents</option>
</select>
