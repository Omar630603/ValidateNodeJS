<div class="">
    <form action="">
        <div class="mt-4">
            <x-input-label for="folder" :value="__('Submit the Source Code')" class="mb-2" />
            <input type="file" name="folder" id="folder" />
            <x-input-error :messages="$errors->get('folder')" class="mt-2" />
        </div>
        <div class="mt-4">
            <x-input-label for="github_link" :value="__('Or Github Link')" />
            <x-text-input id="github_link" class="block mt-1 w-full" type="text" name="github_link"
                :value="old('github_link')" placeholder="E.g. https://github.com/username/repository.git" />
            <x-input-error :messages="$errors->get('github_link')" class="mt-2" />
        </div>
        <div class="flex items-center justify-end mt-12">
            <x-primary-button class="ml-4">
                {{ __('Submit') }}
            </x-primary-button>
        </div>
    </form>
</div>

<script type="text/javascript">
    const inputElement = document.querySelector('input[id="folder"]');
    const pond = FilePond.create(inputElement);
    $('.filepond--credits').hide();
    $('.filepond--panel-root').addClass('bg-gray-900 ');
    $('.filepond--drop-label').addClass('border-2 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-secondary-500 dark:focus:border-secondary-600 focus:ring-secondary-500 dark:focus:ring-secondary-600 rounded-md shadow-sm ');
</script>