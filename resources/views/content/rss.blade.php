{!! '<'.'?xml version="1.0" encoding="UTF-8"?'.'>' !!}
<rss version="2.0"
     xmlns:atom="http://www.w3.org/2005/Atom"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>{{ $meta['title'] }} — ternis.link</title>
    <link>{{ url('/pages/'.$collection) }}</link>
    <description>{{ $meta['subtitle'] }}</description>
    <language>en-us</language>
    <lastBuildDate>{{ $lastBuildDate }}</lastBuildDate>
    <atom:link href="{{ url('/pages/'.$collection.'.xml') }}" rel="self" type="application/rss+xml" />
    <generator>ternis.link</generator>
    @foreach ($entries as $entry)
      <item>
        <title><![CDATA[{{ $entry['title'] }}]]></title>
        <link>{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}</link>
        <guid isPermaLink="true">{{ url('/pages/'.$collection.'/'.$entry['canonical']) }}</guid>
        <pubDate>{{ $entry['pubDate'] }}</pubDate>
        <dc:creator><![CDATA[ternis.link]]></dc:creator>
        <description><![CDATA[{{ $entry['description'] }}]]></description>
        @if (!empty($entry['html']))
        <content:encoded><![CDATA[{!! $entry['html'] !!}]]></content:encoded>
        @endif
      </item>
    @endforeach
  </channel>
</rss>
