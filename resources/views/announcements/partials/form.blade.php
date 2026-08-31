@if($errors->any())
    <div class="rl-form-errors" role="alert"><strong>Please fix the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
<form action="{{ $action }}" method="post" enctype="multipart/form-data">
    @csrf
    @if(($method ?? 'POST') !== 'POST') @method($method) @endif
    <div class="rl-form-field"><label for="announcement-title">Title <span aria-hidden="true">*</span></label><input id="announcement-title" name="title" type="text" maxlength="150" value="{{ old('title', $announcement->title ?? '') }}" placeholder="e.g. New offer live — TechGear Pro" required></div>
    <fieldset class="rl-type-options"><legend>Announcement Type <span aria-hidden="true">*</span></legend>
        @foreach(['new_offer'=>['New Offer','is-new-offer'], 'bonus'=>['Bonus','is-bonus'], 'info'=>['Info','is-info'], 'payments'=>['Payments','is-payments'], 'other'=>['Other','is-other']] as $value=>$type)
            <label class="{{ $type[1] }}"><input type="radio" name="type" value="{{ $value }}" @checked(old('type', $announcement->type ?? 'new_offer') === $value) required><span aria-hidden="true"></span>{{ $type[0] }}</label>
        @endforeach
    </fieldset>
    <div class="rl-form-field"><label for="announcement-body">Announcement Text <span aria-hidden="true">*</span></label><textarea id="announcement-body" name="body" rows="7" maxlength="10000" placeholder="Write your announcement here…" required>{{ old('body', $announcement->body ?? '') }}</textarea></div>
    <div class="rl-form-field"><label for="announcement-attachment">Attachment <small>(optional)</small></label>
        @if(isset($announcement) && $announcement->hasAttachment())
            <div class="rl-current-attachment"><span><i class="fas fa-paperclip" aria-hidden="true"></i> {{ $announcement->attachment_name }}</span><a href="{{ route('announcements.attachment', $announcement) }}">Download</a></div>
        @endif
        <label class="rl-file-drop" for="announcement-attachment"><i class="fas fa-paperclip" aria-hidden="true"></i><span><strong data-file-name>{{ isset($announcement) && $announcement->hasAttachment() ? 'Replace attached file' : 'Click to attach a file' }}</strong><small>PDF, image, spreadsheet, or any document · maximum 10 MB</small></span></label>
        <input class="rl-visually-hidden-file" id="announcement-attachment" name="attachment" type="file">
        @if(isset($announcement) && $announcement->hasAttachment())
            <label class="rl-remove-attachment"><input type="checkbox" name="remove_attachment" value="1" @checked(old('remove_attachment'))><span>Remove the current attachment</span></label>
        @endif
    </div>
    <div class="rl-announcement-options"><strong>Options</strong><label><input type="checkbox" name="is_pinned" value="1" @checked(old('is_pinned', $announcement->is_pinned ?? false))><span>Pin this announcement to the top</span></label></div>
    <footer class="rl-form-actions"><a class="rl-button" href="{{ route('announcements.index') }}">Cancel</a><button class="rl-button rl-primary" type="submit"><i class="far fa-paper-plane" aria-hidden="true"></i> {{ $submitLabel }}</button></footer>
</form>
