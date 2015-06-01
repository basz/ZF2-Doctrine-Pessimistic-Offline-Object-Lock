Pessimistic Offline Object Lock
===============================

A ZF2 module that implements pessimistic offline object lock for any object.

**WORK IN PROGRESS - RFC**

## ObjectLockManager

Manager is dumb

Uses a (memory) table

It uses an object type (get_class($object)) and an object pk (composite pk's are supported)

### Usage

Acquire a lock

```
// acquire a lock on an 'object'
$m->acquireLock('some-object', 'itsPk', 'user-who-locks', 300, 'editing');

// wait some time, and call again will refresh the lock
$m->acquireLock('some-object', 'itsPk', 'user-who-locks');

// refresh lock but now consider it valid for an hour
$m->acquireLock('some-object', 'itsPk', 'user-who-locks', 3600);

// refresh a lock but change the reason
$m->acquireLock('some-object', 'itsPk', 'user-who-locks', null, 'upgrading magic');

```
Check mutation is allowed some time later

```
if ($userWhoLocked = $m->getUserIdent('some-object', 'itsPk')) {
	if ($userWhoLocked === $userWhoWantsToMutate) {
		// continue
	} else {
		// no no!
	}
}

```

Garbage collect

```
$m->relinquishLocks(); // any objects of any type, any user considered (based on ttl) expired.
$m->relinquishLocks(null, 'Some\Object'); // any Some\Object's from any userIdent considered (based on ttl) expired.
$m->relinquishLocks(null, null, 'me'); // any objects of any type from userIdent 'me' considered (based on ttl) expired.
$m->relinquishLocks(null, 'Some\Object', 'me'); // get it?.
```

Free still valid locks

```
$m->relinquishLocks(10); // all object of any type, any user older then 10 seconds.

```

## Written with Doctrine in mind

### Doctrine POOLListener

POOL Checks are performed for entity updates and - removals on the onFlush event.

Question : Can this be optimized? I mean in case of a lock an exception is thrown that will roll back all the work in the unit of work manager. Would it be possible to remove units from the uow manager, commit and then throw an exception?

Question : For every entity updated/removed a read query will be performed: Would it be a usefull idea to withlist entities via configuration or require an interface on entities?

#### AuthenticationService

Question : Obviously one needs to configure an authentication service for the POOL-check to work. getIdentity does not always returns a int|string. How to solve this? I've seen [IdentityProviders](https://github.com/ZF-Commons/zfc-rbac/blob/master/docs/02.%20Quick%20Start.md#specifying-an-identity-provider) but am not sure a similar approach would apply here? Can't rely on a simple ->getId() though...

