<div class="form-group{{ $errors->has($name) ? ' has-error' : '' }}">
    @isset($title)
    {{ Form::label($type === 'radio' ? $name : $name.'[]', $title, ['class' =>  isset($label_class) ? "$label_class control-label" : 'control-label']) }}
    @endisset

    @if (isset($field_wrapper_class))
    <div class="{{ $field_wrapper_class }}">
    @endif

        @foreach($choices as $key => $choice)
            @if(is_array($choice))
                @php($value = isset($choice_value) ? $choice[$choice_value] : $key)
                @php($label = isset($choice_label) ? $choice[$choice_label] : $key)
            @else
                @php($value = isset($choice_value) ? $choice->$choice_value : $choice->id)
                @php($label = isset($choice_label) ? $choice->$choice_label : $choice->__toString())
            @endif
            <div class="checkbox icheck">
                <label>
                    {{ Form::$type($type === 'radio' ? $name : $name.'[]', $value) }} {{ trans($label) }}
                </label>
            </div>
        @endforeach

        @if ($errors->has($name))
            <span class="help-block">
                <strong>{{ $errors->first($name) }}</strong>
            </span>
        @endif
    @if (isset($field_wrapper_class))
    </div>
    @endif
</div>