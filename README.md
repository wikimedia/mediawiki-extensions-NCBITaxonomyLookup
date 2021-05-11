NCBITaxonomyLookup

# Setup

* Clone the repository into the `/extensions` folder
* Add `wfLoadExtension('NCBITaxonomyLookup');` to the bottom of `LocalSettings.php`

# Configuration

* `$wgNCBITaxonomyLookupCacheTTL = 0` time to keep the cached values (in seconds)
* `$wgNCBITaxonomyLookupApiURL` the API url

# Usage

The `#taxonomy` parser functions takes two arguments:

* the taxonomy ID
* the xpath of the XML element to print

Example:

```
{{#taxonomy:12345|Taxon/Rank}}
{{#taxonomy:12345|Taxon/ScientificName}}
{{#taxonomy:45678|Taxon/OtherNames/GenbankCommonName}}
```
