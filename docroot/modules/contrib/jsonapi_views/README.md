# JSON:API Views

## What does this module do?

It creates [JSON:API Resource](https://www.drupal.org/project/jsonapi_resources) for each [Views](https://www.drupal.org/docs/8/core/modules/views) display, allowing for easy consumption of this data outside of Drupal.

When installed the module activates resources for all of your enabled views, you can optionally disable the resource by editing the view.

The URL of the JSON:API Views resource based on your current preview state is displayed while editing a view, this includes filters, pagination and sorts.

## Summary of current features

- JSON:API resource per View display: `/jsonapi/views/{{ viewId }}/{{ displayId }}`
- Pagination: `?page=#`
- Exposed filters: `?views-filter[{{ filter }}]={{ value }}`
- Contextual filters: `?views-argument[]={{ value }`
  - Multiple arguments as such `?views-argument[]={{ value }}&views-argument[]={{ value2 }}`
- Exposed sorts: `?views-sort[sort_by]={{ sortId }}`
