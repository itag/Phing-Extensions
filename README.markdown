Phing Extensions
================

In this repository you find some extensions for the php-based build tool **Phing** (PHing Is Not GNU make; http://phing.info)

Tasks
-----

### MemcacheTask ###

The memcache task supports four different actions:

* add
* delete
* flush
* set

#### Examples ####

    <memcache host="127.0.0.1" port="11211" action="set" key="firstname" value="Mike" />
    <memcache host="127.0.0.1" port="11211" action="add" key="lastname" value="Roetgers" />
    <memcache host="127.0.0.1" port="11211" action="delete" key="firstname" />
    <memcache host="127.0.0.1" port="11211" action="flush" />

#### Memcache Server Pool ####

If you are using a pool of memcache servers, you can define them in your *build.properties* file like this:

    memcache.0.host = 127.0.0.1
    memcache.0.port = 11211
    memcache.1.host = 127.0.0.1
    memcache.1.port = 11212
    memcache.2.host = 127.0.0.1
    memcache.2.port = 11213

In your build file, you can now omit the attibutes "host" and "port". Instead you tell the memcache task to use properties.

    <memcache useProperties="true" action="flush" />
    <memcache useProperties="true" action="set" key="city" value="Berlin" />

Of course you can use the "useProperties"-method just as well with one server.

#### Changing Properties Prefix ####

In some cases the prefix "memcache" can already be reserved for other configuration options in your properties file. Luckily you can easily change the prefix:

    <memcache useProperties="true" propertiesPrefix="myMemcache." action="flush" />

Now the task would look for properties like "myMemcache.0.host".