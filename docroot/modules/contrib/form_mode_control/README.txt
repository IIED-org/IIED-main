WHAT FOR ?

Form Mode Control allows you to use the form modes for any bundle / entity, per role and for edition / modification.
Which means using different forms (fields, order, widgets, etc.) for different roles.

HOW TO USE IT ?

A - DEFAULT FORM MODES

1 - You must at first add form mode for content entities.
Ex. : go to "www.your-site.com/admin/structure/display-modes/form/add" to add a new form mode.
2 - Activate the form mode for the related bundle of the right entity.
Ex.if we want to activate a form mode named Super 2 (machine name = super_2) for an article (entity type : Node, Bundle : Article).
Go to www.your-site.com/admin/structure/types/manage/article/form-display and activate it.
3 - Go to www.your-site.com/admin/people/permissions, a section named Form modes control was added for all form modes activated (and only activated),
So , configure all permissions and give roles permissions to access form modes.
NB, the permission Access all form modes allow you to access to all form modes linked to bundle and entity type.
4 -  Then configure on the Form mode control administration page (www.your-site.com/admin/structure/display-modes/form/config-form-modes) and give for each role a default form modes for creation / edition (of course, each role must have access to the form mode).
  
B - ACCESS DIRECTLY FORM MODES
	
You can also use it with a simple extra query in the URL (?display=machine_name_of_the_form_mode) if the role is allowed to see it.
Of course, the user must have the right permission for it.	
	
Ex. : if you want to access to super_2, go  www.your-site.com/node/add/article?display=super_2.