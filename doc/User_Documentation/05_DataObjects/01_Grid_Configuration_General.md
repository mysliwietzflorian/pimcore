# Grid Configuration & CSV Export

You can use the object grid configurator button to adapt the grid to your needs,
i.e. add additional columns, change the column order or column width.
The chosen column configuration will also be applied for the CSV Export.

![Configurator Button](../img/gridconfig/configurator_button.png)

For experienced users there is an option to assemble custom columns using special operators as described in detail in 
* [Grid Configuration Operators](./02_Grid_Configuration_Operators.md)

## Saving & Sharing

You can define a favourite config for each object folder and share it with your friends.
As an admin user you can also share the config globally (i.e. without having to pick certain users).
Also, you can set the shared config as favourite for all users with whom you share. Setting favourites for shared roles
is not possible, so setting the favourite will only apply to the users.

**Important Note:** If a config, which is shared globally, is set as favourite
a config wich is shared specifically with a user will override that previous favourite. In all other cases the favourites will not be overridden.

![Configurator Button Expanded](../img/gridconfig/configurator_button_expanded.png)

Note that the shared config will always be owned by you. If you delete it then it will be also gone for all
users you shared it with. If you are not the owner of a view you want to share you have to copy it first.

Sharing can be accomplished via the `Save & Share' tab in the configurator window.

![Configurator Button Expanded](../img/gridconfig/shared_users.png)

## Favourites

You can define a preferred config on folder level. If there is another object with a different setting you will have the option
to apply the setting to all folders or just leave it as it is.

![Confirm overwrite all](../img/gridconfig/apply_overwrite_all.png)

## CSV Export Settings

By default, [Inheritance](../../Development_Documentation/05_Objects/01_Object_Classes/05_Class_Settings/25_Inheritance.md) will be disabled.
If you want to enable it or adjust CSV settings do this via the settings dialog.

![Configurator Button Expanded](../img/gridconfig/csv_export_settings.png)

## Batch Editing

![Batch Editing](../img/gridconfig/batch_editing.png)

You modify several objects at once by using the grid's batch update feature. There several modes:

* _Batch edit all_:  Overwrite the field's data for all objects that match the current grid filter (not only the visible ones!)
* _Batch edit selected_: Same but only for the selected ones
* _Batch append to all_: Same as batch edit all but the data will be appended. Currently only supported for relational types.
* _Batch append to selected_: Same as "batch append" but only applied to the selected ones. 

## Filtering

It is possible to filter data on the grid by columns of the following datatypes:
* Text Datatypes 
  - ![Text](../img/gridconfig/filter_text_type_img.png)
* Number Datatypes 
  - ![img.png](../img/gridconfig/filter_number_type_img.png)
* Select Datatypes 
  - ![img.png](../img/gridconfig/filter_select_type_img.png)
* Date Datatypes 
  - ![Date](../img/gridconfig/filter_date_type_img.png)
* Relation Datatypes
  - one to many
  - many to many
  - many to many object
  - advanced many to many
  - advanced many to many object
  - image
  - advanced image
  - ![Relation](../img/gridconfig/filter_relation_type_img.png)
  - Select using search function or drag & drop from the tree
  - ![Relation](../img/gridconfig/filter_relation_type_img2.png)
  - when applied with empty value then only objects with empty relation are shown.



