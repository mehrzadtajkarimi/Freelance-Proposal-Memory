<x-layouts.app :title="__('ui.rules.new_title')">
    <x-page-heading :title="__('ui.rules.new_title')" :description="__('ui.rules.new_description')">
        <x-slot:action><a href="{{ route('rules.index') }}" class="btn-secondary">{{ __('ui.common.back') }}</a></x-slot:action>
    </x-page-heading>

    <form method="POST" action="{{ route('rules.store') }}" class="panel max-w-3xl p-5 sm:p-7">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label class="label" for="title">{{ __('ui.common.title') }}</label>
                <input class="field" id="title" name="title" value="{{ old('title') }}" required maxlength="255" autocomplete="off">
            </div>
            <div>
                <label class="label" for="scope">{{ __('ui.rules.scope') }}</label>
                <select class="field" id="scope" name="scope" required>
                    <option value="global" @selected(old('scope', 'global') === 'global')>{{ __('ui.common.global') }}</option>
                    <option value="project" @disabled($projectRequests->isEmpty()) @selected(old('scope') === 'project')>{{ __('ui.common.project') }}</option>
                </select>
            </div>
            <div>
                <label class="label" for="project_request_id">{{ __('ui.rules.project_request') }}</label>
                <select class="field" id="project_request_id" name="project_request_id">
                    <option value="">{{ __('ui.rules.choose_project') }}</option>
                    @foreach ($projectRequests as $projectRequest)
                        <option value="{{ $projectRequest->id }}" @selected((string) old('project_request_id') === (string) $projectRequest->id)>{{ $projectRequest->title }}</option>
                    @endforeach
                </select>
                @if ($projectRequests->isEmpty())
                    <p class="mt-2 text-xs leading-5 text-slate-500">{{ __('ui.rules.empty_projects') }}</p>
                @endif
            </div>
            <div class="sm:col-span-2">
                <label class="label" for="body">{{ __('ui.rules.body') }}</label>
                <textarea class="field min-h-40 resize-y leading-6" id="body" name="body" rows="7" required maxlength="20000">{{ old('body') }}</textarea>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:justify-end">
            <a href="{{ route('rules.index') }}" class="btn-secondary">{{ __('ui.common.cancel') }}</a>
            <button type="submit" class="btn-primary">{{ __('ui.rules.propose') }}</button>
        </div>
    </form>
</x-layouts.app>
