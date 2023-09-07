### Description

1.) I moved the logic code to App\DB\Query and put it in class. The logic should not be done in controller.

2.) Since the relationship is already established in the models. I leverage the eloquent function with() and whereHas()

```
$qry = $qry->whereHas('sections', function( Builder $q) {
    $q->with('store_products_section')
        ->orderBy('store_products_section.position');
})
```

3.) I create function such as price() and isAvailable() for better readability

4.) There is a lot of code that is not used in old code and opted to remove it.