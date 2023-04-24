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
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3 md:gap-8">
                <div
                    class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg md:col-span-1 md:row-span-1 md:rounded-md md:shadow-md md:py-1 md:px-3 lg:px-5 xl:px-7">
                    <!-- content for the smaller column -->
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <ol class="list-disc list-inside">
                            <li class="list-none">
                                <div class="flex justify-start gap-2">
                                    <x-pending_icon class="w-[20px] h-[20px] pt-[0.10rem]" id="start_pending_icon"
                                        svgWidth='20px' svgHeight='20px' />
                                    <x-success_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]"
                                        id="start_success_icon" svgWidth='20px' svgHeight='20px' />
                                    <x-failed_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]" id="start_failed_icon"
                                        svgWidth='20px' svgHeight='20px' />
                                    <span class="text-gray-400 font-semiblid">Start</span>
                                </div>
                            </li>
                            @forelse ($steps as $step)
                            <li class="list-none">
                                <div class="flex justify-start gap-2">
                                    <x-pending_icon class="w-[20px] h-[20px] pt-[0.10rem]"
                                        id="{{$step->id}}_pending_icon" svgWidth='20px' svgHeight='20px' />
                                    <x-success_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]"
                                        id="{{$step->id}}_success_icon" svgWidth='20px' svgHeight='20px' />
                                    <x-failed_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]"
                                        id="{{$step->id}}_failed_icon" svgWidth='20px' svgHeight='20px' />
                                    <span class="text-gray-400 font-semiblid">{{$step->executionStep->name}}</span>
                                </div>
                            </li>
                            @if ($step->executionStep->name == 'NPM Run Tests')
                            @forelse ($step->variables as $testCommandValue)
                            @php
                            $command = implode(" ",$step->executionStep->commands);
                            $key = explode("=",$testCommandValue)[0];
                            $value = explode("=",$testCommandValue)[1];
                            $testStep = str_replace($key, $value, $command);
                            @endphp
                            <li class="list-none pl-5">
                                <div class="flex justify-start gap-2">
                                    <x-pending_icon class="w-[20px] h-[20px] pt-[0.10rem]"
                                        id="{{$step->id}}_pending_icon_{{$testCommandValue}}" svgWidth='20px'
                                        svgHeight='20px' />
                                    <x-success_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]"
                                        id="{{$step->id}}_success_icon_{{$testCommandValue}}" svgWidth='20px'
                                        svgHeight='20px' />
                                    <x-failed_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]"
                                        id="{{$step->id}}_failed_icon_{{$testCommandValue}}" svgWidth='20px'
                                        svgHeight='20px' />
                                    <span class="text-gray-400 font-semiblid">{{$testStep}}</span>
                                </div>
                            </li>
                            @empty
                            <x-not-found message="No Tests Found" />
                            @endforelse
                            @endif
                            @empty
                            <x-not-found message="No Steps Found" />
                            @endforelse
                            <li class="list-none">
                                <div class="flex justify-start gap-2">
                                    <x-pending_icon class="w-[20px] h-[20px] pt-[0.10rem]" id="done_pending_icon"
                                        svgWidth='20px' svgHeight='20px' />
                                    <x-success_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]" id="done_success_icon"
                                        svgWidth='20px' svgHeight='20px' />
                                    <x-failed_icon class="hidden w-[20px] h-[20px] pt-[0.10rem]" id="done_failed_icon"
                                        svgWidth='20px' svgHeight='20px' />
                                    <span class="text-gray-400 font-semiblid">Done</span>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
                <div
                    class="bg-gray-200 dark:bg-gray-900 border-secondary border-2 overflow-hidden shadow-sm sm:rounded-lg md:col-span-2 md:row-span-1 md:rounded-md md:shadow-md md:py-6 md:px-8 lg:px-12 xl:px-16">
                    <!-- content for the bigger column -->
                    <div class="p-6 text-gray-900 dark:text-gray-100">

                    </div>
                </div>
            </div>
        </div>
    </div>



    @section('scripts')
    <script>
        var submission_status = '{{ $submission->status }}'
        var submission_result = '{{ $submission->result }}'
        var step_id = submission_status = 'pending' ? 0 : 1;
        function checkSubmissionProgress() {
            $.ajax({
                url: '/submissions/process/submission/{{ $submission->id }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token()}}'
                },
                data: {
                    step_id: step_id
                },
                success: function(response) {
                    console.log(step_id);
                    console.log(response.status);
                    console.log(response.message);
                    console.log(response.results);
                    console.log(response);
                    if(response.status == "processing" && step_id == 0){
                        $('#start_pending_icon').addClass('hidden');
                        $('#start_success_icon').removeClass('hidden');
                        step_id = 1;
                    }
                    setTimeout(checkSubmissionProgress, 1000);
                },
                error: function(error) {
                    console.log(step_id);
                    console.log(error.status);
                    console.log(error.message);
                    console.log(error.results);
                    console.log(error);
                }
            });
        }
        checkSubmissionProgress();
    </script>
    @endsection
    @endif
</x-app-layout>