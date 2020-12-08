# Menus

### Refactoring

Near complete re-write as of r11491, the goals of which were:
  * To remove unnecessary dependencies of all menus on User class
  * To code to interfaces rather than concrete classes as much as possible.

### Interfaces

MenuInterface describes a simple menu which contains menu items. NavBarInterface describes the bootstrap-style menus
which contain distinct left-hand and right-hand menu items.

* MenuInterface
  * getMenuItems() // MenuItemInterface[]

* NavBarInterface
  * getLeftMenuItems() // MenuItemInterface[]
  * getRightMenuItems() // MenuItemInterface[]

MenuItemInterface describes a standard menu item:

* MenuItemInterface
  * getChildren() // MenuItemInterface[]
  * hasChildren() // boolean
  * getId() // string
  * getTitle() // string
  * getUrl() // string
  * getOptions() / array

### Classes

* Menu, NavBar and MenuItem provide simple value object implementations of the corresponding interfaces.
* RoleMenuItem extends the MenuItem implementation to allow an array of roles to be stored with each MenuItem.
* RoleMenu and RoleNavBar extend their counterparts to provide menus that filter their menu items (RoleMenuItem[]) based
  upon the roles available to the currently logged-in user).
* RoleFilterTrait contains the filtering code which is used by both RoleMenu and RoleNavBar.
* MenuDivider and RoleMenuDivider provide menu dividers (in both plain and role-specific versions)

### Dividers

Dividers are implemented as a MenuItem with empty id, title, url and no children, but helper classes
exist to hide this implementation detail and make their use simple.
