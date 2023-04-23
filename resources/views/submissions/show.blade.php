<x-app-layout>
    @if(request()->routeIs('submissions.showAll'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('All submissions for project: ') . $project->title }}
        </h2>
    </x-slot>
    @elseif(request()->routeIs('submissions.show'))
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Submission NO#').$submission->id.(' of project: ') . $submission->project->title }}
        </h2>
    </x-slot>

    <div class="py-5">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <ul class="list-disc list-inside">
                        <x-success_icon />
                        <x-failed_icon />
                        <x-pending_icon />
                        <span class="font-semibold">Cloning the repo</span>
                        <li>
                            <span class="font-semibold">Running tests</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    @section('scripts')
    <script>
        function checkSubmissionProgress() {
            // send a request to the server to get the submission status
            $.ajax({
                url: '/submissions/process/submission/{{ $submission->id }}',
                method: 'POST',
                headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token()}}'
                    },
                success: function(response) {
                    // update the UI based on the submission status
                    console.log(response);
                },
                error: function(error) {
                    console.log(error);
                }
            });
        }
        checkSubmissionProgress();
    </script>
    @endsection
    @endif
</x-app-layout>