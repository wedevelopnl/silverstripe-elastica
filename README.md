# silverstripe-elastica

```
ELASTICSEARCH_INDEX=silverstripe
ELASTICSEARCH_HOST=elasticsearch
ELASTICSEARCH_PORT=9200
```

# DateFilter options
If you want to add/overrule/exclude options:
```
TheWebmen\Elastica\Filters\DateFilter:
  mapping:
    Today:
      From: today midnight
      To: tomorrow midnight
      Label: Vandaag
    Since 7 days:
      Exclude: true
    Since 30 days:
      From: -30 days midnight
      To: tomorrow midnight
      Label: Sinds 30 dagen
    Since 6 months:
      From: -6 months midnight
      To: tomorrow midnight
      Label: Sinds 6 maanden 
      
```
