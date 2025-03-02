@if($rating)
    @php
        $filledStars = Str::repeat('☭', round($rating));
        $emptyStars  = Str::repeat('☆', round((5 - $rating)));
    @endphp
    
    {{ "$filledStars$emptyStars" }}
@else
    No rating yet
@endif