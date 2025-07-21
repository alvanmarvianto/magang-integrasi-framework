import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
    faPlus,
    faPencil,
    faTrash,
    faSort,
    faSortUp,
    faSortDown
} from '@fortawesome/free-solid-svg-icons';

// Add icons to the library
library.add(
    faPlus,
    faPencil,
    faTrash,
    faSort,
    faSortUp,
    faSortDown
);

export { FontAwesomeIcon }; 