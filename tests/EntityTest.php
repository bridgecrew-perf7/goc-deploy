<?php

use Marcth\GocDeploy\Exceptions\MassAssignmentException;
use PHPUnit\Framework\TestCase;
use Marcth\GocDeploy\Entities\Entity;


class EntityStub extends Entity
{
    protected $hidden = ['password'];

    protected $casts = [
        'age'   => 'integer',
        'score' => 'float',
        'data'  => 'array',
        'active' => 'bool',
        'secret' => 'string',
        'count' => 'int',
        'object_data' => 'object',
        'collection_data' => 'collection',
        'foo' => 'bar',
    ];

    protected $guarded = [
        'secret',
    ];

    protected $fillable = [
        'name',
        'city',
        'age',
        'score',
        'data',
        'active',
        'count',
        'object_data',
        'default',
        'collection_data',
    ];

    public function getListItemsAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setListItemsAttribute($value)
    {
        $this->attributes['list_items'] = json_encode($value);
    }

    public function setBirthdayAttribute($value)
    {
        $this->attributes['birthday'] = strtotime($value);
    }

    public function getBirthdayAttribute($value)
    {
        return date('Y-m-d', $value);
    }

    public function getAgeAttribute($value): int
    {
        $date = DateTime::createFromFormat('U', $this->attributes['birthday']);

        return $date->diff(new DateTime('now'))->y;
    }

    public function getTestAttribute($value): string
    {
        return 'test';
    }
}

class EntityTest extends TestCase
{
    public function testAttributeManipulation()
    {
        $entity = new EntityStub;
        $entity->name = 'foo';

        $this->assertEquals('foo', $entity->name);
        $this->assertTrue(isset($entity->name));
        unset($entity->name);
        $this->assertEquals(null, $entity->name);
        $this->assertFalse(isset($entity->name));

        $entity['name'] = 'foo';
        $this->assertTrue(isset($entity['name']));
        unset($entity['name']);
        $this->assertFalse(isset($entity['name']));
    }

    public function testConstructor()
    {
        $entity = new EntityStub(['name' => 'john']);
        $this->assertEquals('john', $entity->name);
    }

    public function testNewInstanceWithAttributes()
    {
        $entity = new EntityStub;
        $instance = $entity->newInstance(['name' => 'john']);

        $this->assertInstanceOf('EntityStub', $instance);
        $this->assertEquals('john', $instance->name);
    }

    public function testHidden()
    {
        $entity = new EntityStub;
        $entity->password = 'secret';

        $attributes = $entity->attributesToArray();
        $this->assertFalse(isset($attributes['password']));
        $this->assertEquals(['password'], $entity->getHidden());
    }

    public function testVisible()
    {
        $entity = new EntityStub;
        $entity->setVisible(['name']);
        $entity->name = 'John Doe';
        $entity->city = 'Paris';

        $attributes = $entity->attributesToArray();
        $this->assertEquals(['name' => 'John Doe'], $attributes);
    }

    public function testToArray()
    {
        $entity = new EntityStub;
        $entity->name = 'foo';
        $entity->bar = null;
        $entity->password = 'password1';
        $entity->setHidden(['password']);
        $array = $entity->toArray();

        $this->assertTrue(is_array($array));
        $this->assertEquals('foo', $array['name']);
        $this->assertFalse(isset($array['password']));
        $this->assertEquals($array, $entity->jsonSerialize());

        $entity->addHidden(['name']);
        $entity->addVisible('password');
        $array = $entity->toArray();
        $this->assertTrue(is_array($array));
        $this->assertFalse(isset($array['name']));
        $this->assertTrue(isset($array['password']));
    }

    public function testToJson()
    {
        $entity = new EntityStub;
        $entity->name = 'john';
        $entity->foo = 10;

        $object = new stdClass;
        $object->name = 'john';
        $object->foo = 10;

        $this->assertEquals(json_encode($object), $entity->toJson());
        $this->assertEquals(json_encode($object), (string) $entity);
    }

    public function testMutator()
    {
        $entity = new EntityStub;
        $entity->list_items = ['name' => 'john'];
        $this->assertEquals(['name' => 'john'], $entity->list_items);
        $attributes = $entity->getAttributes();
        $this->assertEquals(json_encode(['name' => 'john']), $attributes['list_items']);

        $birthday = strtotime('245 months ago');

        $entity = new EntityStub;
        $entity->birthday = '245 months ago';

        $this->assertEquals(date('Y-m-d', $birthday), $entity->birthday);
        $this->assertEquals(20, $entity->age);
    }

    public function testToArrayUsesMutators()
    {
        $entity = new EntityStub;
        $entity->list_items = [1, 2, 3];
        $array = $entity->toArray();

        $this->assertEquals([1, 2, 3], $array['list_items']);
    }

    public function testReplicate()
    {
        $entity = new EntityStub;
        $entity->name = 'John Doe';
        $entity->city = 'Paris';

        $clone = $entity->replicate();
        $this->assertEquals($entity, $clone);
        $this->assertEquals($entity->name, $clone->name);
    }

    public function testAppends()
    {
        $entity = new EntityStub;
        $array = $entity->toArray();
        $this->assertFalse(isset($array['test']));

        $entity = new EntityStub;
        $entity->setAppends(['test']);
        $array = $entity->toArray();
        $this->assertTrue(isset($array['test']));
        $this->assertEquals('test', $array['test']);
    }

    public function testArrayAccess()
    {
        $entity = new EntityStub;
        $entity->name = 'John Doe';
        $entity['city'] = 'Paris';

        $this->assertEquals($entity->name, $entity['name']);
        $this->assertEquals($entity->city, $entity['city']);
    }

    public function testSerialize()
    {
        $entity = new EntityStub;
        $entity->name = 'john';
        $entity->foo = 10;

        $serialized = serialize($entity);
        $this->assertEquals($entity, unserialize($serialized));
    }

    public function testCasts()
    {
        $entity = new EntityStub;
        $entity->score = '0.34';
        $entity->data = ['foo' => 'bar'];
        $entity->count = 1;
        $entity->object_data = ['foo' => 'bar'];
        $entity->active = 'true';
        $entity->default = 'bar';
        $entity->collection_data = [['foo' => 'bar', 'baz' => 'bat']];

        $this->assertTrue(is_float($entity->score));
        $this->assertTrue(is_array($entity->data));
        $this->assertTrue(is_bool($entity->active));
        $this->assertTrue(is_int($entity->count));
        $this->assertEquals('bar', $entity->default);
        $this->assertInstanceOf('\stdClass', $entity->object_data);
        $this->assertInstanceOf('\Illuminate\Support\Collection', $entity->collection_data);

        $attributes = $entity->getAttributes();
        $this->assertTrue(is_string($attributes['score']));
        $this->assertTrue(is_string($attributes['data']));
        $this->assertTrue(is_string($attributes['active']));
        $this->assertTrue(is_int($attributes['count']));
        $this->assertTrue(is_string($attributes['default']));
        $this->assertTrue(is_string($attributes['object_data']));
        $this->assertTrue(is_string($attributes['collection_data']));

        $array = $entity->toArray();
        $this->assertTrue(is_float($array['score']));
        $this->assertTrue(is_array($array['data']));
        $this->assertTrue(is_bool($array['active']));
        $this->assertTrue(is_int($array['count']));
        $this->assertEquals('bar', $array['default']);
        $this->assertInstanceOf('\stdClass', $array['object_data']);
        $this->assertInstanceOf('\Illuminate\Support\Collection', $array['collection_data']);
    }

    public function testGuarded()
    {
        $entity = new EntityStub(['secret' => 'foo']);
        $this->assertTrue($entity->isGuarded('secret'));
        $this->assertNull($entity->secret);
        $this->assertContains('secret', $entity->getGuarded());

        $entity->secret = 'bar';
        $this->assertEquals('bar', $entity->secret);

        EntityStub::unguard();

        $this->assertTrue(EntityStub::isUnguarded());
        $entity = new EntityStub(['secret' => 'foo']);
        $this->assertEquals('foo', $entity->secret);

        EntityStub::reguard();
    }

    public function testGuardedCallback()
    {
        EntityStub::unguard();
        $mock = $this->getMockBuilder('stdClass')
            ->setMethods(['callback'])
            ->getMock();
        $mock->expects($this->once())
            ->method('callback')
            ->will($this->returnValue('foo'));
        $string = EntityStub::unguarded([$mock, 'callback']);
        $this->assertEquals('foo', $string);
        EntityStub::reguard();
    }

    public function testTotallyGuarded()
    {
        $this->expectException(MassAssignmentException::class);

        $entity = new EntityStub();
        $entity->guard(['*']);
        $entity->fillable([]);
        $entity->fill(['name' => 'John Doe']);
    }

    public function testFillable()
    {
        $entity = new EntityStub(['foo' => 'bar']);
        $this->assertFalse($entity->isFillable('foo'));
        $this->assertNull($entity->foo);
        $this->assertNotContains('foo', $entity->getFillable());

        $entity->foo = 'bar';
        $this->assertEquals('bar', $entity->foo);

        $entity = new EntityStub;
        $entity->forceFill(['foo' => 'bar']);
        $this->assertEquals('bar', $entity->foo);
    }

    public function testHydrate()
    {
        $entities = EntityStub::hydrate([['name' => 'John Doe']]);
        $this->assertEquals('John Doe', $entities[0]->name);
    }
}
