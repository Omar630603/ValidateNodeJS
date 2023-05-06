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
            {{ __('Submission NO#').$submission->id.__(' of Project: ') . $submission->project->title .__(' attempt NO#: ').$submission->attempts }}
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
                    <div id="refresh"
                        class="hidden float-right cursor-pointer mx-5 mt-1 motion-reduce:animate-[spin_1.5s_linear_infinite]">
                        <x-refresh_icon class="h-8 w-8" svgWidth='2rem' svgHeight='2rem' />
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
        $(document).ready(function () {
        // Left side steps
        const startElement = $(`.stepNames:contains(Start)`).closest('li');
        const stepNames = $('.stepNames');
        const doneElement = $(`.stepNames:contains(Done)`).closest('li');
        // Right side submission results
        const submission_message = $('#submission_message');
        const submission_status = $('#submission_status');
        const submission_results = $('#submission_results');
        // Right side progress indicators
        const barElement = $('#bar');
        const loaderElement = $('#loader');
        const refreshElement = $('#refresh');
        // varibale for faild submission, to be used in the refresh button
        let allowedToRefresh = true;
        // variable for the progress bar
        let completion_percentage = 0;
        // get the current submission
        let currentSubmission = {
            results: `<?php echo json_encode($submission->results); ?>`,
            status: '<?php echo $submission->status; ?>',
            message: '<?php echo $submission->message; ?>',
        }
        // update the UI with the current submission
        if (currentSubmission.results !== null) {
            updateUI(currentSubmission);
        }  
        // animate the progess bar
        function move(start = 0, end = 100) {
            var width = start;
            barElement.width(width + '%').html(width + '%');
            var duration = 500; // duration of the animation in ms
            barElement.stop().animate({width: end + '%'}, {
                duration: duration * (end - start) / 100,
                step: function(now, fx) {
                var val = Math.round(now);
                barElement.html(val + '%');
                }
            });
        }
        // send request to the server to check the submission progress
        async function checkSubmissionProgress() {
            try {
                // get the response from the server
                const response = await $.ajax({
                    url: `/submissions/process/submission/{{ $submission->id }}`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
                // update the UI with the response
                updateUI(response);
                // move the progress bar if the completion percentage has changed
                if (completion_percentage !== response.completion_percentage) {
                    completion_percentage = response.completion_percentage;
                    move(0, response.completion_percentage);
                }
                // check the status of the submission
                // processing status
                if (response.status === 'processing') {
                    if (response.next_step?.id !== undefined) {
                        // the submission is still processing
                        loaderElement.removeClass('hidden');
                        window.requestAnimationFrame(checkSubmissionProgress);
                    } else {
                        // the end of the submission steps
                        loaderElement.addClass('hidden');
                    }
                } else if (response.status === 'failed') {
                // failed status
                    updateUIFailedStatus(response);
                } else if (response.status === 'completed') {
                // completed status
                    loaderElement.addClass('hidden');
                    updateUICompletedStatus(response);
                }
            } catch (error) {
                // if the request failed
                const error_response = {
                    status: 'failed',
                    message: 'Something went wrong'
                };
                updateUIFailedStatus(error_response);
                console.log(error);
            }
        }

        checkSubmissionProgress();

        function updateUIFailedStatus(response){
            // hide the loader
            loaderElement.addClass('hidden');
            // change the done icon to failed
            doneElement.find('#done_pending_icon').addClass('hidden');
            doneElement.find('#done_failed_icon').removeClass('hidden');
            doneElement.find('span').addClass('text-red-400');
            // show error message
            submission_status.text("Submssion Status: " + response.status);
            submission_message.text("Submssion Message: " + response.message);
            // move the progress bar to 100% and red color
            move(0, 100);
            barElement.removeClass('bg-secondary');
            barElement.addClass('bg-red-400');
            // show the refresh button
            refreshElement.removeClass('hidden');
            if (allowedToRefresh){
                refreshElement.click(async function(){
                    // prevent the user from clicking the button multiple times
                    allowedToRefresh = false;
                    // update the submission results and progress bar
                    move(0, 0);
                    submission_message.text("Submssion Message: Restarting");
                    submission_message.text("Submssion Message: Restarting...");
                    // request the server to refresh the submission
                    await requestRefresh();
                });
            }
            // update the submission results last element
            const submission_results_done = $('#submission_results_done');
            submission_results_done.children('p').text("Status: " + response.status);
            submission_results_done.children('p').removeClass("text-gray-400");
            submission_results_done.children('p').removeClass("text-secondary");
            submission_results_done.children('p').addClass("text-red-400");
            submission_results_done.children('p').next().text("Message: " + response.message);
            submission_results_done.children('p').next().removeClass("text-red-400");
        }

        async function requestRefresh() {
            // start the refresh loader
            refreshElement.addClass('animate-spin');
            try {
                const response = await $.ajax({
                    url: '/submissions/refresh/submission/{{ $submission->id }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token()}}'
                    }
                });
                // update the UI with the response
                refreshElement.removeClass('animate-spin');
                refreshElement.addClass('hidden');
                barElement.removeClass('bg-red-400');
                barElement.addClass('bg-secondary');
                // refresh the page
                window.location.reload();
            } catch (error) {
                console.log(error);
                const error_response = {
                    status: "failed",
                    message: "Something went wrong",
                };
                updateUIFailedStatus(error_response);
            }
        }

        function updateUICompletedStatus(response){
            // hide the loader
            loaderElement.addClass('hidden');
            // change the done icon to success
            doneElement.find('#done_pending_icon').addClass('hidden');
            doneElement.find('#done_success_icon').removeClass('hidden');
            doneElement.find('span').addClass('text-secondary');
            // show the submission results
            submission_status.text("Submssion Status: " + response.status);
            submission_message.text("Submssion Message: " + response.message);
            // move the progress bar to 100% and green color
            move(0, 100);
            barElement.removeClass('bg-secondary');
            barElement.addClass('bg-green-400');
            // update the submission results last element
            const submission_results_done = $('#submission_results_done');
            submission_results_done.children('p').text("Status: " + response.status);
            submission_results_done.children('p').removeClass("text-red-400");
            submission_results_done.children('p').removeClass("text-gray-400");
            submission_results_done.children('p').addClass("text-secondary");
            submission_results_done.children('p').next().text("Message: " + response.message);
            submission_results_done.children('p').next().removeClass("text-secondary");
        }

        function updateUI(response){
            if (response.status && response.message && response.results) {
                // number of steps
                number = 1;
                // clean the submission results
                submission_results.empty();
                // update the submission results
                submission_status.text("Submssion Status: " + response.status); 
                submission_message.text("Submssion Message: " + response.message);
                // order the steps by order
                let arr = Object.entries(response.results);
                arr.sort((a, b) => parseInt(a[1].order) - parseInt(b[1].order));
                results = Object.fromEntries(arr);
                // update the submission results first step start
                startElement.find('#start_pending_icon').addClass('hidden');
                startElement.find('span').addClass('text-secondary');
                startElement.find('#start_success_icon').removeClass('hidden');
                submission_results.append('<p class="text-center m-0 p-0">Results Summary</p>'); 
                submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_start">
                            <h1 class="text-md font-bold">${number}- Start</h1>
                            <p class="text-xs font-semibold text-secondary-400">Status: Done</p>
                            <p class="text-xs font-semibold">Output: Submission has been initialized and ready to process</p>
                            </div>`);
                // check all the steps status and update the UI
                for (const [stepName, stepData] of Object.entries(results)) {
                    const stepElement = $(`.stepNames:contains(${stepName})`).closest('li');
                    if (stepData.status === 'completed') {
                        stausClass = 'text-secondary';
                        outputClass = 'break-words';
                        stepElement.find(`#${stepData.stepID}_pending_icon`).addClass('hidden');
                        stepElement.find(`#${stepData.stepID}_success_icon`).removeClass('hidden');
                        stepElement.find(`#${stepData.stepID}_failed_icon`).addClass('hidden');
                        stepElement.find('span').removeClass('text-gray-400');
                        stepElement.find('span').removeClass('text-red-400');
                        stepElement.find('span').addClass('text-secondary');
                    } else if (stepData.status === 'failed') {
                        stausClass = 'text-red-400';
                        outputClass = 'break-words';
                        stepElement.find(`#${stepData.stepID}_pending_icon`).addClass('hidden');
                        stepElement.find(`#${stepData.stepID}_success_icon`).addClass('hidden');
                        stepElement.find(`#${stepData.stepID}_failed_icon`).removeClass('hidden');
                        stepElement.find('span').removeClass('text-secondary');
                        stepElement.find('span').removeClass('text-gray-400');
                        stepElement.find('span').addClass('text-red-400');
                    } else {
                        stausClass = 'text-gray-400';
                        outputClass = 'text-gray-400 break-words';
                        stepElement.find(`#${stepData.stepID}_pending_icon`).removeClass('hidden');
                        stepElement.find(`#${stepData.stepID}_success_icon`).addClass('hidden');
                        stepElement.find(`#${stepData.stepID}_failed_icon`).addClass('hidden');
                        stepElement.find('span').removeClass('text-red-400');
                        stepElement.find('span').removeClass('text-secondary');
                        stepElement.find('span').addClass('text-gray-400');
                    }
                    number += 1;                
                    submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_${stepData.stepID}">
                            <h1 class="text-md font-bold">${number}- ${stepName}</h1>
                            <p class="text-xs font-semibold ${stausClass}">Status: ${stepData.status}</p>
                            <p class="text-xs font-semibold ${outputClass}">Output: ${stepData.output}</p>
                            </div>`);
                }
                // add the last step done
                submission_results.append(`<div class="text-lg text-white mt-5 border p-5" id="submission_results_done">
                    <h1 class="text-md font-bold">${number}- Done</h1>
                    <p class="text-xs font-semibold text-gray-400">Status: Pending</p>
                    <p class="text-xs font-semibold text-gray-400 break-words">Output:</p>
                    </div>`);
            }
        }
    });
    </script>
    @endsection
    @endif
</x-app-layout>