import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
    faPlus,
    faPencil,
    faTrash
} from '@fortawesome/free-solid-svg-icons';

// Add icons to the library
library.add(
    faPlus,
    faPencil,
    faTrash
);

export { FontAwesomeIcon }; 