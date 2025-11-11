# Files That Need Toast Import Updates

These files are still importing from the OLD `vue-toastification` library.
They need to be updated to use your custom toast system.

## Files to Update:

1. ✅ **WorkStationMapping.vue** - FIXED
2. **Users.vue** - Line 17
3. **Laboratory.vue** - Line 4
4. **AssignedComputers.vue** - Line 23
5. **Dashboard.vue** - Line 6
6. **ComputerLogs.vue** - Line 5
7. **Computers.vue** - Line 5
8. **Register.vue** - Line 8
9. **StudentAssignmentModal.vue** - Line 149

## Find and Replace:

### OLD:
```javascript
import { useToast } from 'vue-toastification';
```

### NEW:
```javascript
import { useToast } from '../../composable/toastification/useToast';
// Note: Adjust the path based on file location
// From pages/*/*.vue: ../../composable/toastification/useToast
// From components/*.vue: ../composable/toastification/useToast
```

## Also Check Toast Call Signatures:

### OLD (vue-toastification):
```javascript
toast.success('message');
toast.error('message');
```

### NEW (custom toast):
```javascript
toast.success('Title', 'Description');
toast.error('Title', 'Description');
// OR just title if no description
toast.success('Operation completed successfully');
```

## Important Notes:

1. The OLD library is trying to initialize itself on import
2. This is why you see random toasts appearing
3. ALL imports must be changed to the custom toast
4. Make sure to use proper title/description format
