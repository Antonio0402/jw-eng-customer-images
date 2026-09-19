JW_Eng_Customer_Images\Core\Controller
===============

Abstract class to define/implement base methods for all controller classes




* Class name: Controller
* Namespace: JW_Eng_Customer_Images\Core
* This is an **abstract** class





Properties
----------


### $model

    protected Object $model

Holds Model object



* Visibility: **protected**


### $view

    protected Object $view

Holds View Object



* Visibility: **protected**


Methods
-------


### get_instance

    object JW_Eng_Customer_Images\Core\Controller::get_instance(mixed $model_class_name, mixed $view_class_name)

Provides access to a single instance of a module using the singleton pattern



* Visibility: **public**
* This method is **static**.


#### Arguments
* $model_class_name **mixed** - &lt;p&gt;Model Class to be associated with the controller.&lt;/p&gt;
* $view_class_name **mixed** - &lt;p&gt;View Class to be associated with the controller.&lt;/p&gt;



### get_model

    object JW_Eng_Customer_Images\Core\Controller::get_model()

Get model.

In most of the cases, the model will be set as per routes in defined in routes.php.
So if you are not sure which model class is currently being used, search for the
current controller class name in the routes.php

* Visibility: **protected**




### get_view

    object JW_Eng_Customer_Images\Core\Controller::get_view()

Get view

In most of the cases, the view will be set as per routes in defined in routes.php.
So if you are not sure which view class is currently being used, search for the
current controller class name in the routes.php

* Visibility: **protected**




### set_model

    void JW_Eng_Customer_Images\Core\Controller::set_model(\JW_Eng_Customer_Images\Core\Model $model)

Sets the model to be used



* Visibility: **protected**


#### Arguments
* $model **[JW_Eng_Customer_Images\Core\Model](JW_Eng_Customer_Images-Core-Model.md)** - &lt;p&gt;Model object to be associated with the current controller object.&lt;/p&gt;



### set_view

    void JW_Eng_Customer_Images\Core\Controller::set_view(\JW_Eng_Customer_Images\Core\View $view)

Sets the view to be used



* Visibility: **protected**


#### Arguments
* $view **[JW_Eng_Customer_Images\Core\View](JW_Eng_Customer_Images-Core-View.md)** - &lt;p&gt;View object to be associated with the current controller object.&lt;/p&gt;



### __construct

    mixed JW_Eng_Customer_Images\Core\Controller::__construct(\JW_Eng_Customer_Images\Core\Model $model, mixed $view)

Constructor



* Visibility: **protected**


#### Arguments
* $model **[JW_Eng_Customer_Images\Core\Model](JW_Eng_Customer_Images-Core-Model.md)** - &lt;p&gt;Model object to be used with current controller object.&lt;/p&gt;
* $view **mixed** - &lt;p&gt;View object to be used with current controller object. Otherwise false.&lt;/p&gt;



### init

    void JW_Eng_Customer_Images\Core\Controller::init(\JW_Eng_Customer_Images\Core\Model $model, mixed $view)

Sets Model & View to be used with current controller



* Visibility: **protected**


#### Arguments
* $model **[JW_Eng_Customer_Images\Core\Model](JW_Eng_Customer_Images-Core-Model.md)** - &lt;p&gt;Model to be associated with this controller.&lt;/p&gt;
* $view **mixed** - &lt;p&gt;Either View/its child class object or False.&lt;/p&gt;


