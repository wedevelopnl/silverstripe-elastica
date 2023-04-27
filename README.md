# silverstripe-elastica

# Usage of SearchableObjectExtension

This is the base extension for all of your searchable objects.

In your `elastica.yml` add the following configuration for the object:

  ```
  App\Object\MySearchableObject:
    extensions:
      - WeDevelop\Elastica\Extensions\SearchableObjectExtension
    elastica:
      index_names:
      - my-searchable-object // if read-only is false, only first index will be used to write to
      read-only: false  // true if you will only read from index
      document_name_prefix: My_Searchable_Object  // if not defined, object class name wil be used
      fields:
        Fieldname1:
          type: keyword
        Fieldname2: 
          type: text
        ...
 ```
Fields can also be defined in extend hook `updateElasticaFields`

To fill the documents with data, use the extend hook `updateElasticaDocumentData`

# Usage of PageExtension
Make sure your page already uses `SearchableObjectExtension`.

````
App\Page\MyPage:
  extensions:
    - WeDevelop\Elastica\Extensions\SearchableObjectExtension
    - WeDevelop\Elastica\Extensions\PageExtension
  elastica:
    include_grid_elements: true   // if you want to include (some of) grid elements
````
If you want to include grid elements into search, you need to pass extra config parameter the it's class:
```
App\Grid\MyGridElement:
 search_indexable: true
```

# Usage of ShowInSearchAwareOfExtension
Use it when you need to exclude some object from search.
Make sure your object already uses `SearchableObjectExtension`.
Can also be applied to grid elements.

```
App\Page\MyPage:
  extensions:
    - WeDevelop\Elastica\Extensions\SearchableObjectExtension
    - WeDevelop\Elastica\Extensions\PageExtension
    - WeDevelop\Elastica\Extensions\ShowInSearchAwareOfExtension
    
App\Grid\MyGridElement:
 extensions:
    - WeDevelop\Elastica\Extensions\ShowInSearchAwareOfExtension
 search_indexable: true
```

# Usage of FilterPageExtension and FilterPageControllerExtension

Use it to add a filter form.

````
App\Page\FilterPage:
  extensions:
    - WeDevelop\Elastica\Extensions\FilterPageExtension
  elastica:
    filter_class:  App\Object\MySearchableObject
    filters:   
     - 
      class: WeDevelop\Elastica\Filters\DateFilter
      name: Date
      id: date_filter
      title: Datum
      field_name: StartDate \\ a field defined in App\Object\MySearchableObject elastica config
      sort: 
 App\Controller\FilterPageController
   extensions:
    - WeDevelop\Elastica\Extensions\FilterPageControllerExtension
      
````
`FilterForm` can be extended wih `updateFilterForm`