---
title: JavaScript events
sidebar: auto
---

# JavaScript events

During the interaction you have with a DataView, there are various JavaScript events being dispatched. These events are
dispatched on the `<div>` that contains the DataView.

## On view changes

Any change you perform on the DataView, be it sorting, hiding or showing fields, changing the pagination settings, or
even move to the next page, triggers two events:

- `datakit/view/change` This event is dispatched before the change is displayed on the page.
    - `event.details.id` Contains the ID of the DataView
    - `event.details.old` Contains (a copy of) the state of the DataView before the change is applied
    - `event.details.new` Contains (a reference to) the state of the DataView after the change is applied.
- `datakit/view/changed` This event is dispatched after the change is displayed on the page.
    - `event.details.id` Contains the ID of the DataView.
    - `event.details.view` Contains (a copy of) the state of the DataView now that it is changed.

Since the events `bubble` you can listen to them on the `document` object. The `event.target` element will then point
to the `<div>` the DataView is rendered in.

## On view selection

When your DataView has bulk actions, you can select the entries for which this action should be performed. For every
selection the following event is dispatched:

- `datakit/view/selection` This event is dispatched after the selection changed.
  - `event.details.id` Contains the ID of the DataView.
  - `event.details.items` Contains an array of the selected item ID's.
