<form action="/search" method="GET">
    <input type="text" name="query" placeholder="Search posts...">
    <button type="submit">Search</button>
</form>

<ul>
    @foreach ($posts as $post)
        <li>{{ $post->title }}</li>
    @endforeach
</ul>

{{ $posts->links() }}
