<div class="sidebar bg-light border-right" style="width: 250px; height: 100vh; position: fixed;">
    <div class="">
    <ul>
        @foreach ($units as $unit)
            <li>{{ $unit->name }}</li>
        @endforeach
    </ul>
    </div>
</div>
