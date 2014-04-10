If Stackview does not return any results, make sure that /json/temp is set to belong to the group www-data and is set to 0775.  For example root:www-data.  This will allow temporary json files to be written.
Also, if the json files seem to be building up.  See if the cleaner function has permission to remove them.
