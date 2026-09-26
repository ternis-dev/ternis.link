# {{ $meta['title'] }}

> {{ $meta['subtitle'] }} [HTML version]({{ url('/pages/'.$collection) }})

@foreach ($entries as $entry)
- {{ $entry['date'] }} — [{{ $entry['title'] }}]({{ url('/pages/'.$collection.'/'.$entry['slug']) }}){{ $entry['description'] !== '' ? ' — '.$entry['description'] : '' }} ([Markdown]({{ url('/pages/'.$collection.'/'.$entry['slug'].'.md') }}))
@endforeach
