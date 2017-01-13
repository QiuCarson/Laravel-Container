<?php
interface Visit{
    public function go();
}
class Leg implements Visit{
    public function go(){
        echo 'walt to Tibet!!!';
    }
}
class Car implements Visit{
    public function go(){
        echo "drive car to Tibet!!!";
    }
}
class Train implements Visit{
    public function go(){
        echo "go to Tibet by train!!!";
    }
}




class Container{
    protected $bindings=[];
    //全局变量保持闭包函数
    public function bind($abstract,$concrete=null,$shared=false){
        //如果这个不是一个闭包函数，就用getClosure创建一个闭包函数
        if(!$concrete instanceof Closure){
            $concrete=$this->getClosure($abstract,$concrete);
        }
        //保存成多位数组
        $this->bindings[$abstract]=compact('concrete','shared');
    }
    //生成一个闭包函数
    protected function getClosure($abstract,$concrete){
        //这里的$c对应build方法$concrete($this);中的this
        return function($c) use($abstract,$concrete){
            $method=($abstract==$concrete)?'build':'make';
            return $c->$method($concrete);
        };
    }
    public function make($abstract){
        $concrete=$this->getConcrete($abstract);
        //判断是否是一个闭包函数
        if($this->isBuildable($concrete,$abstract)){
            //是一个闭包函数就实例化
            $object=$this->build($concrete);
        }else{
            $object=$this->make($concrete);
        }
        return $object;
    }
    //判断是否是一个闭包函数
    protected function isBuildable($concrete,$abstract){
        return $concrete===$abstract||$concrete instanceof Closure;
    }
    //判断全局变量$this->bindings里有没有注入这个类
    protected function getConcrete($abstract){
        if(!isset($this->bindings[$abstract])){
            return $abstract;
        }
        return $this->bindings[$abstract]['concrete'];
    }
    //用反射的方式实例化类
    public function build($concrete){
        //如果是一个闭包函数就直接返回
        if($concrete instanceof Closure){
            return $concrete($this);
        }
        //反射实例化类
        $reflector=new ReflectionClass($concrete);
        //反射的方式判断类是否可以实例化
        if(!$reflector->isInstantiable()){
            echo $message="Target [$concrete] is not instantiable.";
        }
        //判断有没有构造函数
        $constructor=$reflector->getConstructor();
        //如果没有构造函数就直接实例化
        if(is_null($constructor)){
            return new $concrete;
        }
        //反射构造函数的参数
        $dependencies=$constructor->getParameters();

        $instances=$this->getDependencies($dependencies);
        return $reflector->newInstanceArgs($instances);
    }
    protected function getDependencies($parameters){
        $dependencies=[];
        foreach($parameters as $parameter){
            //反射方式参数的接口比如Traveller类中的$trafficTool的变量必须是Visit这个类型的
            $dependency=$parameter->getClass();

            if(is_null($dependency)){
                $dependencies[]=NULL;
            }else{
                //如果要有限制的参数，在注入这个类
                $dependencies[]=$this->resolveClass($parameter);
            }
        }
        return (array)$dependencies;
    }
    protected function resolveClass(ReflectionParameter $parameter){

        return $this->make($parameter->getClass()->name);
    }

}
class Traveller{
    protected $trafficTool;
    public function __construct(Visit $trafficTool)
    {
        $this->trafficTool=$trafficTool;
    }
    public function visitTibet(){
        $this->trafficTool->go();
    }
}
$app=new Container();
$app->bind("Visit","Train");
$app->bind("traveller","Traveller");
$tra=$app->make("traveller");
$tra->visitTibet();