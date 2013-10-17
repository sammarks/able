core = 7
api = 2

; Custom Modules
; --------------
; Want to add new modules to use on the site? They go here!
; projects[module_name][subdir] = "contrib"

; Modules
; -------

projects[admin_menu][subdir] = "contrib"
projects[ctools][subdir] = "contrib"
projects[field_group][subdir] = "contrib"
projects[google_analytics][subdir] = "contrib"
projects[libraries][subdir] = "contrib"
projects[nodequeue][subdir] = "contrib"
projects[pathauto][subdir] = "contrib"
projects[rubik][subdir] = "contrib"
projects[smtp][subdir] = "contrib"
projects[tao][subdir] = "contrib"
projects[views][subdir] = "contrib"
projects[views_bulk_operations][subdir] = "contrib"
projects[xmlsitemap][subdir] = "contrib"
projects[power_menu][subdir] = "contrib"
projects[module_filter][subdir] = "contrib"
projects[metatag][subdir] = "contrib"
projects[less][subdir] = "contrib"
projects[conditional_styles][subdir] = "contrib"
projects[redirect][subdir] = "contrib"
projects[token][subdir] = "contrib"
projects[entity][subdir] = "contrib"
projects[defaultcontent][subdir] = "contrib"
projects[context][subdir] = "contrib"
projects[xautoload][subdir] = "contrib"
projects[features][subdir] = "contrib"
projects[field_collection][subdir] = "contrib"

; Libraries
; ---------

libraries[jquery][download][type] = "file"
libraries[jquery][download][url] = "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"

libraries[lessphp][type] = "libraries"
libraries[lessphp][download][type] = "git"
libraries[lessphp][download][url] = "https://github.com/leafo/lessphp.git"

; AE Libraries
; ------------

projects[ac_global][type] = "module"
projects[ac_global][subdir] = "contrib"
projects[ac_global][download][type] = "git"
projects[ac_global][download][url] = "https://ableengine:%40bl33ng1n3@ableengine.git.beanstalkapp.com/ablecore-drupal-global.git"

projects[ac_base][subdir] = "contrib"
projects[ac_base][type] = "theme"
projects[ac_base][download][type] = "git"
projects[ac_base][download][url] = "https://ableengine:%40bl33ng1n3@ableengine.git.beanstalkapp.com/ablecore-drupal-base-theme.git"

projects[ac_admin][subdir] = "contrib"
projects[ac_admin][type] = "theme"
projects[ac_admin][download][type] = "git"
projects[ac_admin][download][url] = "https://ableengine:%40bl33ng1n3@ableengine.git.beanstalkapp.com/ablecore-drupal-admin-theme.git"

libraries[ac_libs][type] = "libraries"
libraries[ac_libs][download][type] = "git"
libraries[ac_libs][download][url] = "https://ableengine:%40bl33ng1n3@ableengine.git.beanstalkapp.com/ablecore-drupal-libraries.git"
