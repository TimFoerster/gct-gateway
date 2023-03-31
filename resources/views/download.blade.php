@extends('layout')

@section('body')


    <div class="flex grow flex-row justify-between gap-2 w-full overflow-hidden mt-4" >
        <div class="panel grow-2">
            <h3>Builds</h3>
            <div>
                <a href="/storage/linux.zip">Linux</a>
            </div>
            <div>
                <a href="/storage/windows.zip">Windows</a>
            </div>
        </div>
    </div>

@endsection
