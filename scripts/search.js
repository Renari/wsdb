var cards = new Bloodhound({
  datumTokenizer: function (datum) {
    return Bloodhound.tokenizers.whitespace(datum.name);
  },
  queryTokenizer: Bloodhound.tokenizers.whitespace,
  remote: {
    url: '/api/search/%QUERY',
    wildcard: '%QUERY'
  },
});
$('#search .typeahead').typeahead(null, {
  name: 'cards',
  display: 'name',
  limit: 10,
  source: cards.ttAdapter()
});
$('#search .typeahead').on('typeahead:selected', function (e, datum) {
    gotocard(datum.cardno);
}).on('typeahead:autocompleted', function (e, datum) {
    gotocard(datum.cardno);
});
function gotocard(cardid)
{
  window.location.href = '/card/' + encodeURIComponent(cardid.toLowerCase().replace(/[\/_]/, '-'));
}
