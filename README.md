# Emhar ApiInfrastructure Bundle

This bundle adapts common symfony bundles used for a REST application:
* FOSRestBundle
* NelmioApiDocBundle
* JmsSerializerBundle
* FervoEnumBundle

## FOSRestBundle & JmsSerializerBundle adaptation

FOSRestBundle has a request body param converter.
It uses serializer to deserialize request body, valid it and pass it to controller.

* Without this adaptation:

If an error occurred during deserialization process, deserialization is stopped.

A unique constraint violation with an untranslated message is provided to controller.

If deserialization successes but validation fails, multiple constraint violations are provided to controller.

* With this adaptation:

If an error occurred during deserialization process, deserialization continue.

Constraint violations of deserialization are merged with those of validation.

Common messages (date and enum) of deserialization constraint violations are translated.

## NelmioApiDocBundle & JmsSerializerBundle adaptations

Adapts nelmio parser.

* Validation parser:
   * Tableize field name.
   * Verify from serializer if field is in serialization group

* Serializer parser
   * Add information of fields of sub type in case of target classes with inheritance mapping.
      
      a special description is added to stored which sub types allow field usage.