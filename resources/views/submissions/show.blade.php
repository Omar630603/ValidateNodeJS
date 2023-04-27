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
                                    <span class="text-gray-400 font-semiblid stepNames">Start</span>
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
                                    <span
                                        class="text-gray-400 font-semiblid stepNames">{{$step->executionStep->name}}</span>
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
                                    <span class="text-gray-400 font-semiblid stepNames">{{$testStep}}</span>
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
                                    <span class="text-gray-400 font-semiblid stepNames">Done</span>
                                </div>
                            </li>
                        </ol>
                    </div>
                </div>
                <div
                    class="bg-gray-200 dark:bg-gray-900 border-secondary border-2 overflow-hidden shadow-sm sm:rounded-lg md:col-span-2 md:row-span-1 md:rounded-md md:shadow-md md:py-6 md:px-8 lg:px-12 xl:px-16">
                    <!-- content for the bigger column -->

                    <div class="m-5 w-100 bg-gray-600 sm:rounded-lg md:rounded-md" id="progress">
                        <div class="text-center w-[1%] h-5 bg-secondary text-white sm:rounded-lg md:rounded-md text-sm"
                            id="bar">0%</div>
                    </div>
                    <div id="loader"
                        class="mx-5 mt-1 float-right hidden h-8 w-8 animate-spin rounded-full border-4 border-solid border-current border-r-transparent align-[-0.125em] text-secondary motion-reduce:animate-[spin_1.5s_linear_infinite]"
                        role="status">
                        <span
                            class="!absolute !-m-px !h-px !w-px !overflow-hidden !whitespace-nowrap !border-0 !p-0 ![clip:rect(0,0,0,0)]">Loading...</span>
                    </div>
                    <div class="mx-5">
                        <p class="text-white text-xl" id="submission_message"></p>
                        <p class="text-white text-xl" id="submission_status"></p>
                    </div>
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="text-white mt-5" id="submission_results">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>



    @section('scripts')
    <script>

    </script>
    <script>
        const submission_message = $('#submission_message');
        const submission_status = $('#submission_status');
        const submission_results = $('#submission_results');
        const startElement = $(`.stepNames:contains(Start)`).closest('li');
        const doneElement = $(`.stepNames:contains(Done)`).closest('li');
        const loaderElement = $('#loader');

        var stepsCount = 0;
        var completedStepsCount = 1;

        var currentSubmission = {};
        currentSubmission.results = `<?php echo json_encode($submission->results); ?>`;
        currentSubmission.status = '<?php echo $submission->status; ?>';
        currentSubmission.message = '<?php echo $submission->message; ?>';

        if (currentSubmission.results !== null) {
            updateUI(currentSubmission);
        }  
  

        startElement.find('#start_pending_icon').addClass('hidden');
        startElement.find('span').addClass('text-secondary');
        startElement.find('#start_success_icon').removeClass('hidden');

        var stepNames = $('.stepNames');
        
        function move(start = 0, end = 100) {
            var elem = document.getElementById("bar");
            var width = start;
            var id = setInterval(frame, 10);
            function frame() {
              if (width >= end) {
                clearInterval(id);
                i = 0;
              } else {
                width++;
                elem.style.width = width + "%";
                elem.innerHTML = width  + "%";
              }
            }
        }

        function checkSubmissionProgress() {
            $.ajax({
                url: '/submissions/process/submission/{{ $submission->id }}',
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token()}}'
                },
                success: function(response) {
                    updateUI(response);
                    if(response.status == "processing"){
                        if (response.next_step?.id !== undefined){
                            loaderElement.removeClass('hidden');
                            setTimeout(checkSubmissionProgress, 1000);
                        }
                    }else if (response.status == "failed"){
                        updateUIFailedStatus(response);
                    }
                },
                error: function(error) { 
                    error_response = {};
                    error_response.status = "failed";
                    error_response.message = "Something went wrong";
                    console.log(error);
                    updateUIFailedStatus(error_response);
                }
            });
        }

        checkSubmissionProgress();

        function updateUIFailedStatus(response){
            loaderElement.addClass('hidden');
            doneElement.find('#done_pending_icon').addClass('hidden');
            doneElement.find('#done_failed_icon').removeClass('hidden');
            doneElement.find('span').addClass('text-red-400');
            submission_status.text("Submssion Status: " + response.status);
            submission_message.text("Submssion Message: " + response.message);
            move(0, 100);
            $('#bar').removeClass('bg-secondary');
            $('#bar').addClass('bg-red-400');
        }

        function updateUI(response){
            number = 2;
            submission_results.empty();
            submission_status.text("Submssion Status: " + response.status); 
            submission_message.text("Submssion Message: " + response.message);
            let arr = Object.entries(response.results);
            arr.sort((a, b) => parseInt(a[1].stepID) - parseInt(b[1].stepID));
            results = Object.fromEntries(arr);
            
            stepsCount = Object.keys(results).length;

            submission_results.append('<p class="text-center m-0 p-0">Results Summary</p>'); 
            submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_start">
                        <h1 class="text-md font-bold">1- Start</h1>
                        <p class="text-xs font-semibold text-secondary-400">Status: Done</p>
                        <p class="text-xs font-semibold">Output: Submission has been initialized and ready to process</p>
                        </div>`);

            for (const [stepName, stepData] of Object.entries(results)) {
                const stepElement = $(`.stepNames:contains(${stepName})`).closest('li');
                if (stepData.status === 'completed') {
                    stepElement.find(`#${stepData.stepID}_pending_icon`).addClass('hidden');
                    stepElement.find(`#${stepData.stepID}_success_icon`).removeClass('hidden');
                    stepElement.find(`#${stepData.stepID}_failed_icon`).addClass('hidden');
                    stepElement.find('span').addClass('text-secondary');

                    submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_${stepData.stepID}">
                        <h1 class="text-md font-bold">${number}- ${stepName}</h1>
                        <p class="text-xs font-semibold text-secondary">Status: ${stepData.status}</p>
                        <p class="text-xs font-semibold break-words">Output: ${stepData.output}</p>
                        </div>`);
                    
                } else if (stepData.status === 'failed') {
                    stepElement.find(`#${stepData.stepID}_pending_icon`).addClass('hidden');
                    stepElement.find(`#${stepData.stepID}_success_icon`).addClass('hidden');
                    stepElement.find(`#${stepData.stepID}_failed_icon`).removeClass('hidden');
                    stepElement.find('span').addClass('text-red-400');

                    submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_${stepData.stepID}">
                        <h1 class="text-md font-bold">${number}- ${stepName}</h1>
                        <p class="text-xs font-semibold text-red-400">Status: ${stepData.status}</p>
                        <p class="text-xs font-semibold break-words">Output: ${stepData.output}</p>
                        </div>`);
                } else {
                    stepElement.find(`#${stepData.stepID}_pending_icon`).removeClass('hidden');
                    stepElement.find(`#${stepData.stepID}_success_icon`).addClass('hidden');
                    stepElement.find(`#${stepData.stepID}_failed_icon`).addClass('hidden');
                    stepElement.find('span').addClass('text-gray-400');

                    submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_${stepData.stepID}">
                        <h1 class="text-md font-bold">${number}- ${stepName}</h1>
                        <p class="text-xs font-semibold text-gray-400">Status: ${stepData.status}</p>
                        <p class="text-xs font-semibold text-gray-400 break-words">Output: ${stepData.output}</p>
                        </div>`);
                }
                number += 1;                
            }

            move(0, (completedStepsCount / stepsCount ) * 100);

            submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_done">
                <h1 class="text-md font-bold">${number}- Done</h1>
                <p class="text-xs font-semibold text-gray-400">Status: Pending</p>
                <p class="text-xs font-semibold text-gray-400 break-words">Output:</p>
                </div>`);

        }
        
        move(0, (completedStepsCount / stepsCount) * 100);

    </script>
    @endsection
    @endif
</x-app-layout>