# TODO and IDEAS

## TODO

### Collections
1. Update Set to accept a set of types in the constructor.
2. Make TypeSet as simple as possible and remove dependency on Set class.
3. Remove the "Of" classes and make Set, Sequence, and Dictionary all accept types.
4. Remove src iterable from the constructors and instead use append().
5. Complete tests for collection classes.

### Math
1. Make Complex class into an extension. (maybe later)
2. Complete tests for Complex class.


## IDEAS

A Date class could allow for easy date manipulation, including:
   - Date::today()
   - Date::tomorrow()
   - Date::yesterday()
   - Date::add()
   - Date::sub()
   - Date::diff()
   - startOfWeek()
   - year()
   - month()
   - day()
   - dayOfWeek()
   - week()
   - startOfWeek()
   - endOfWeek()
   - startOfMonth()
   - endOfMonth()
   - startOfYear()
   - endOfYear()
   - Operator - (Date - Date or Date - int days)
   - Operator + (Date + int days)
   - Operator ==, ===, !=, !==
   - Operator <, >, <=, >=
   - toJulianDayNumber()
   - fromJulianDayNumber()
   - toRataDie()
   - fromRateDie()
   - age()
   - toDateTime()
   - fromDateTime()
   - hasLeapSecond()
   - parse()
   - format()
   - __toString()

Not sure of usefulness though.
