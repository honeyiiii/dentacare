@php
    $base = collect($base ?? [])->all();
    $more = collect($more ?? [])->all();
    $durations = collect($durations ?? [])->all();

    /*
    |--------------------------------------------------------------------------
    | Selected symptoms
    |--------------------------------------------------------------------------
    */

    $selected = old('symptoms', []);

    /*
    |--------------------------------------------------------------------------
    | Result from controller
    |--------------------------------------------------------------------------
    */

    $result = session('result');

    /*
    |--------------------------------------------------------------------------
    | Custom symptoms
    |--------------------------------------------------------------------------
    */

    $custom = array_values(
        array_diff($selected, $base, $more)
    );

    /*
    |--------------------------------------------------------------------------
    | Show "More" symptoms if one is selected
    |--------------------------------------------------------------------------
    */

    $showMore = count(
        array_intersect($selected, $more)
    ) > 0;

    /*
    |--------------------------------------------------------------------------
    | Urgency classes
    |--------------------------------------------------------------------------
    */

    $pillClass = [
        'High' => 'bad',
        'Medium' => '',
        'Low' => 'ok',
    ];
@endphp


<x-app-layout>

    <h2>Symptom Checker</h2>

    <p class="sub">
        Describe how you feel to get a predicted result.
        This is NOT a diagnosis.
    </p>


    {{-- ========================================================= --}}
    {{-- VALIDATION ERRORS --}}
    {{-- ========================================================= --}}

    @if ($errors->any())
        <div
            class="note"
            style="margin-bottom:12px;color:#b91c1c"
        >
            @foreach ($errors->all() as $e)
                <div>{{ $e }}</div>
            @endforeach
        </div>
    @endif


    <div class="two e">


        {{-- ===================================================== --}}
        {{-- LEFT SIDE - SYMPTOM FORM --}}
        {{-- ===================================================== --}}

        <div class="card">

            <b>1. Your symptoms</b>


            <form
                method="POST"
                action="{{ route('symptoms.store') }}"
                id="symform"
            >

                @csrf


                {{-- Hidden symptoms will be inserted here --}}
                <div id="hiddenbox"></div>


                {{-- ================================================= --}}
                {{-- AREA --}}
                {{-- ================================================= --}}

                <label for="area">
                    Select area
                </label>

                <input
                    id="area"
                    name="area"
                    value="{{ old('area') }}"
                    placeholder="Upper left, lower right, gums..."
                >


                {{-- ================================================= --}}
                {{-- SYMPTOMS --}}
                {{-- ================================================= --}}

                <label>
                    Symptoms
                </label>


                <div class="chips">

                    {{-- Base symptoms --}}
                    @foreach ($base as $s)

                        <button
                            type="button"
                            class="chip {{ in_array($s, $selected) ? 'on' : '' }}"
                            data-s="{{ $s }}"
                        >
                            {{ $s }}
                        </button>

                    @endforeach


                    {{-- Custom symptoms from previous submission --}}
                    @foreach ($custom as $s)

                        <button
                            type="button"
                            class="chip on"
                            data-s="{{ $s }}"
                        >
                            {{ $s }}
                        </button>

                    @endforeach


                    {{-- More button --}}
                    <button
                        type="button"
                        class="chip"
                        id="addbtn"
                    >
                        + Add
                    </button>

                </div>


                {{-- ================================================= --}}
                {{-- MORE SYMPTOMS --}}
                {{-- ================================================= --}}

                <div
                    class="chips"
                    id="morechips"
                    style="
                        display:{{ $showMore ? 'flex' : 'none' }};
                        margin-top:10px
                    "
                >

                    @foreach ($more as $s)

                        <button
                            type="button"
                            class="chip {{ in_array($s, $selected) ? 'on' : '' }}"
                            data-s="{{ $s }}"
                        >
                            {{ $s }}
                        </button>

                    @endforeach

                </div>


                {{-- ================================================= --}}
                {{-- OTHER SYMPTOM --}}
                {{-- ================================================= --}}

                <label for="other">
                    Other symptom (optional)
                </label>

                <input
                    id="other"
                    placeholder="Type another symptom"
                >


                {{-- ================================================= --}}
                {{-- PAIN LEVEL --}}
                {{-- ================================================= --}}

                <label for="pain">

                    Pain level (1 to 10):

                    <b id="pv">
                        {{ old('pain_level', 5) }}
                    </b>

                </label>

                <input
                    type="range"
                    id="pain"
                    name="pain_level"
                    min="1"
                    max="10"
                    value="{{ old('pain_level', 5) }}"
                >


                {{-- ================================================= --}}
                {{-- DURATION --}}
                {{-- ================================================= --}}

                <label for="duration">
                    How long
                </label>

                <select
                    name="duration"
                    id="duration"
                >

                    <option value="">
                        Select duration
                    </option>

                    @foreach ($durations as $d)

                        <option
                            value="{{ $d }}"
                            {{ old('duration') === $d ? 'selected' : '' }}
                        >
                            {{ $d }}
                        </option>

                    @endforeach

                </select>


                {{-- ================================================= --}}
                {{-- NOTES --}}
                {{-- ================================================= --}}

                <label for="notes">
                    Additional notes
                </label>

                <textarea
                    name="notes"
                    id="notes"
                    rows="3"
                    placeholder="Describe anything else you are experiencing..."
                >{{ old('notes') }}</textarea>


                <br>
                <br>


                {{-- ================================================= --}}
                {{-- SUBMIT --}}
                {{-- ================================================= --}}

                <button
                    type="submit"
                    class="btn f"
                >
                    Run prediction
                </button>

            </form>

        </div>



        {{-- ===================================================== --}}
        {{-- RIGHT SIDE - RESULT --}}
        {{-- ===================================================== --}}

        <div class="card">

            <b>2. Predicted result</b>


            @if ($result)

                {{-- ================================================= --}}
                {{-- URGENCY --}}
                {{-- ================================================= --}}

                <div
                    class="top"
                    style="margin-top:12px"
                >

                    <div>

                        <b>
                            URGENCY
                        </b>

                        <p class="sub">
                            {{ $result['window'] ?? '' }}
                        </p>

                    </div>


                    <span
                        class="pill {{ $pillClass[$result['urgency'] ?? ''] ?? '' }}"
                    >
                        {{ $result['urgency'] ?? '' }}
                    </span>

                </div>


                {{-- ================================================= --}}
                {{-- POSSIBLE CONDITIONS --}}
                {{-- ================================================= --}}

                <b style="font-size:12px">
                    POSSIBLE CONDITIONS
                </b>

                <br>
                <br>


                @foreach (($result['conditions'] ?? []) as $c)

                    @php

                        $name = $c['name'] ?? '';

                        $pct = $c['pct'] ?? 0;

                    @endphp


                    <div style="margin-bottom:4px">

                        {{ $name }}

                        <strong>
                            {{ $pct }}%
                        </strong>

                    </div>


                    <div class="bar">

                        <i
                            style="width:{{ $pct }}%"
                        ></i>

                    </div>


                    <br>

                @endforeach


                {{-- ================================================= --}}
                {{-- NEXT STEP --}}
                {{-- ================================================= --}}

                <div class="note">

                    <b>
                        Suggested next step
                    </b>

                    <br>

                    {{ $result['step'] ?? '' }}

                </div>


                <br>


                {{-- ================================================= --}}
                {{-- BOOK APPOINTMENT --}}
                {{-- ================================================= --}}

                <a
                    class="btn"
                    style="
                        text-decoration:none;
                        display:inline-block
                    "
                    href="{{ route('appointments.create') }}"
                >
                    Book appointment
                </a>


                {{-- ================================================= --}}
                {{-- START OVER --}}
                {{-- ================================================= --}}

                <a
                    class="btn o"
                    style="
                        text-decoration:none;
                        display:inline-block
                    "
                    href="{{ route('symptoms.index') }}"
                >
                    Start over
                </a>


            @else

                {{-- ================================================= --}}
                {{-- NO RESULT YET --}}
                {{-- ================================================= --}}

                <p
                    class="sub"
                    style="margin-top:12px"
                >
                    Choose your symptoms and run the prediction
                    to see results here.
                </p>

            @endif

        </div>

    </div>



    {{-- ========================================================= --}}
    {{-- JAVASCRIPT --}}
    {{-- ========================================================= --}}

    <script>

        /*
        |--------------------------------------------------------------------------
        | SYMPTOM CHIPS
        |--------------------------------------------------------------------------
        */

        document
            .querySelectorAll('.chip[data-s]')
            .forEach(function (button) {

                button.addEventListener('click', function () {

                    button.classList.toggle('on');

                });

            });


        /*
        |--------------------------------------------------------------------------
        | SHOW / HIDE MORE SYMPTOMS
        |--------------------------------------------------------------------------
        */

        var addButton = document.getElementById('addbtn');

        if (addButton) {

            addButton.addEventListener('click', function () {

                var more = document.getElementById('morechips');

                if (more.style.display === 'none') {

                    more.style.display = 'flex';

                } else {

                    more.style.display = 'none';

                }

            });

        }


        /*
        |--------------------------------------------------------------------------
        | PAIN SLIDER
        |--------------------------------------------------------------------------
        */

        var range = document.getElementById('pain');

        if (range) {

            range.addEventListener('input', function () {

                document.getElementById('pv').textContent =
                    range.value;

            });

        }


        /*
        |--------------------------------------------------------------------------
        | FORM SUBMIT
        |--------------------------------------------------------------------------
        */

        var form = document.getElementById('symform');

        if (form) {

            form.addEventListener('submit', function () {

                var box =
                    document.getElementById('hiddenbox');

                /*
                | Clear previously generated hidden inputs
                */

                box.innerHTML = '';


                /*
                | Prevent duplicates
                */

                var seen = {};


                /*
                |--------------------------------------------------------------------------
                | Add symptom to hidden inputs
                |--------------------------------------------------------------------------
                */

                function add(value) {

                    if (!value) {
                        return;
                    }

                    if (seen[value]) {
                        return;
                    }

                    seen[value] = true;


                    var input =
                        document.createElement('input');

                    input.type = 'hidden';

                    input.name = 'symptoms[]';

                    input.value = value;


                    box.appendChild(input);

                }


                /*
                |--------------------------------------------------------------------------
                | Get selected chips
                |--------------------------------------------------------------------------
                */

                document
                    .querySelectorAll('.chip.on[data-s]')
                    .forEach(function (chip) {

                        add(chip.dataset.s);

                    });


                /*
                |--------------------------------------------------------------------------
                | Get custom symptom
                |--------------------------------------------------------------------------
                */

                var other =
                    document.getElementById('other');


                if (other) {

                    var otherValue =
                        other.value.trim();

                    if (otherValue) {

                        add(otherValue);

                    }

                }

            });

        }

    </script>

</x-app-layout>