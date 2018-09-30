<?php



class Soltani {

    protected $user,$username='',$password='',$token,$syncid='',$time=30,$lasttime,$catgory,$mahak,$wocom,$attrib,$texo=[],$abb;
    protected static $api;
    public function __construct()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mahak';
        $config= $wpdb->get_row("SELECT * FROM $table_name WHERE id=1");
        $this->username = $config->username;
        $this->password = $config->password;
        $this->syncid = $config->syncid;
        $this->time = $config->time;
        $this->user = $config->userid;
        $this->lasttime = $config->lastime;
        $this->attrib=json_decode(str_replace("\\",'',$config->data));
        if(!file_exists(plugin_dir_path(__FILE__) . "/data.csv"))
            return;
            $ad = plugin_dir_path(__FILE__)."data.csv";
            $myfile = fopen($ad, "r") ;
            $csv = fread($myfile,filesize($ad));

            fclose($myfile);
        $arr = explode("\n",$csv);
        $abb =[];
        foreach ($arr as $value)
        {
            
            $ex = explode(",",$value);
            if(count($ex)<=1)
                break;
            $abb[$ex[0]]=$ex[1];
        }
        $this->abb=$abb;

        
    }

    public function Getform()
    {
        if(!isset($_POST['submit']))
            return;
        $time = $_POST['time'];
        $username = $_POST['username'];
        $password = $_POST['password'];
        $syncid = $_POST['syncid'];
        $attrib = $_POST['attrib'];
        if($this->Gettoken($username,$password)=="error")
            return false;
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'mahak',["data"=>$attrib,"time"=>$time,"syncid"=>$syncid,"username"=>$username,"password"=>$password,"userid"=>1,"lastime"=>time()],["id"=>1]);
        $this->username=$username;
        $this->password=$password;
        $this->syncid=$syncid;
        $this->time=$time;
        $this->attrib=json_decode(str_replace("\\","", $attrib));
        if(isset($_FILES['data'])) {
            $file_tmp = $_FILES['data']['tmp_name'];
            move_uploaded_file($file_tmp, plugin_dir_path(__FILE__) . "/data.csv");
        }
        return true;
    }

    public function run()
    {
        if($_GET['catdownload']==1)
        {
         print_r($this->Getterms());
         die();
        }
       if(!$this->Timerun())
            return;
        $this->getattrib();
        global $wpdb;
        $wpdb->update($wpdb->prefix . 'mahak',["lastime"=>time()],["id"=>1]);
        $this->Getdata();
        $this->catgory=$this->Getterms();
        $wo = $this->wocom;

        foreach ($this->mahak as $key => $value)
        {
            
            if(isset($wo[$key]))
            {
                if ($wo[$key]['_price']['value'] != $value['price'])
                {
                    $wpdb->update($wpdb->postmeta,["meta_value"=>$value['price']],["post_id"=>$wo[$key]['id'],"meta_key"=>"_price"]);
                    $wpdb->update($wpdb->postmeta,["meta_value"=>$value['price']],["post_id"=>$wo[$key]['id'],"meta_key"=>"_regular_price"]);
                    $wpdb->update($wpdb->postmeta,["meta_value"=>$value['price']],["post_id"=>$value['uniq'],"meta_key"=>"_price","meta_value"=>$wo[$key]['_price']['value']]);
                }
                if ($wo[$key]['_stock']['value'] != $value['count'])
                    $wpdb->update($wpdb->postmeta,["meta_value"=>$value['count']],["post_id"=>$wo[$key]['id'],"meta_key"=>"_stock"]);
            }
            elseif(isset($wo[$value['uniq']])&&$value['size']!='')
            {
                $this->Addproductchild($value,$wo[$value['uniq']],$key);
            }
            elseif(!isset($wo[$value['uniq']]))
            {
              $id=$this->Addproduct($value,$value['size']!=''?false:true);
              $d1=$wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID = $id AND post_parent = 0 ;");
              $wo[$value['uniq']]=['id'=>$d1->ID,'title'=>$d1->post_title,'name'=>$d1->post_name,'type'=>$d1->post_type,'guid'=>$d1->guid,"parent"=>$d1->post_parent];
              $wpdb->update($wpdb->posts,["guid"=>get_option( 'siteurl' )."?post_type=product&#038;p=$id"],["ID"=>$id]);
              if($value['size']!='')
              $this->Addproductchild($value,$wo[$value['uniq']],$value['uniq'].' - '.$this->texo[$value['size']]['name']);
            }
        }
    }

    protected function Addproductchild($data,$key,$k)
    {
        $meta=
            array(
                "_wc_review_count"=>0,
                //"_wc_rating_count"=>'a:0:{}',
                "_wc_average_rating"=>0,
                "_edit_last"=>0,
                "_edit_lock"=>0,
                //"_sku"=>$key,
                "_regular_price"=>$data['price'],
               // "_sale_price"=>$data['price'],
                "_sale_price_dates_from"=>'',
                "_sale_price_dates_to"=>'',
                "total_sales"=>0,
                "_tax_status"=>'taxable',
                "_tax_class"=>'parent',
                "_manage_stock"=>'yes',
                "_backorders"=>'no',
                "_sold_individually"=>'yes',
                "_weight"=>'',
                "_length"=>'',
                "_width"=>'',
                "_height"=>'',
                // "_upsell_ids"=>'a:0:{}',
                // "_crosssell_ids"=>'a:0:{}',
                "_purchase_note"=>'',
                // "_default_attributes"=>'a:0:{}',
                "_virtual"=>'no',
                "_downloadable"=>'no',
                "_product_image_gallery"=>'',
                "_download_expiry"=>-1,
                "_download_limit"=>-1,
                "_stock"=>$data['count'],
                "_stock_status"=>'instock',
                "_product_version"=>'3.4.3',
                "_price"=>$data['price'],
                "_suk"=>'',
                "_variation_description"=>'',

            );
        $post=
            [
                "post_author"=>$this->user,
                "post_title"=>$k,
                "post_name"=>$data['uniq'],
                "post_status"=>'publish',
                "post_type"=>'product_variation',
                "post_parent"=>$key['id'],
                "comment_status"=>'closed',
                "ping_status"=>'closed',
                "meta_input"=>$meta,




            ];
        global $wpdb;
        $wpdb->insert($wpdb->term_relationships,["object_id"=>$key['id'],"term_taxonomy_id"=>$this->texo[$data['size']]['id'],"term_order"=>0]);
        $wpdb->insert($wpdb->postmeta,["post_id"=>$key['id'],"meta_key"=>"_price","meta_value"=>$data['price']]);
        $i = wp_insert_post($post);
        $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"attribute_pa_%d8%b3%d8%a7%db%8c%d8%b2","meta_value"=>$this->texo[$data['size']]['name']]);
        $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_upsell_ids","meta_value"=>"a:0:{}"]);
        $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_crosssell_ids","meta_value"=>"a:0:{}"]);
        $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_default_attributes","meta_value"=>"a:0:{}"]);
        $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_wc_rating_count","meta_value"=>"a:0:{}"]);
        $wpdb->update($wpdb->posts,["post_title"=>$k],["ID"=>$i]);
    }

    protected function Addproduct($data,$uniq)
    {
        
        if($uniq) {
            $meta =
                array(
                    "_wc_review_count" => 0,
                    //        "_wc_rating_count"=>'a:0:{}',
                    "_wc_average_rating" => 0,
                    "_edit_last" => 0,
                    "_edit_lock" => 0,
                    "_suk" => $data['uniq'],
                    "_regular_price" => $data['price'],
                    "_sale_price" => $data['price'],
                    "_sale_price_dates_from" => '',
                    "_sale_price_dates_to" => '',
                    "total_sales" => 0,
                    "_tax_status" => 'taxable',
                    "_tax_class" => '',
                    "_manage_stock" => 'yes',
                    "_backorders" => 'no',
                    "_sold_individually" => 'yes',
                    "_weight" => '',
                    "_length" => '',
                    "_width" => '',
                    "_height" => '',
                    // "_upsell_ids"=>'a:0:{}',
                    // "_crosssell_ids"=>'a:0:{}',
                    "_purchase_note" => '',
                    // "_default_attributes"=>'a:0:{}',
                    "_virtual" => 'no',
                    "_downloadable" => 'no',
                    "_product_image_gallery" => '',
                    "_download_expiry" => -1,
                    "_download_limit" => -1,
                    "_stock" => $data['count'],
                    "_stock_status" => 'instock',
                    "_product_version" => '3.4.3',
                    "_price" => $data['price'],
                );
            $post=
                [
                    "post_author"=>$this->user,
                    "post_title"=>$data['uniq'],
                    "post_status"=>'publish',
                    "post_type"=>'product',
                    "comment_status"=>'open',
                    "ping_status"=>'closed',
                    "tax_input"=>["product_cat"=>explode('*',$this->Getcat($data['cat'])),"product_type"=>[$this->texo['simple']]],
                    "meta_input"=>$meta,




                ];
        }
        else {
            $meta =
                array(
                    "_wc_review_count" => 0,
                    // "_wc_rating_count"=>'',
                    "_wc_average_rating" => 0,
                    "_edit_last" => 0,
                    "_edit_lock" => 0,
                    "_sku" => $data['uniq'],
                    "_regular_price" => '',
                    "_sale_price" => '',
                    "_sale_price_dates_from" => '',
                    "_sale_price_dates_to" => '',
                    "total_sales" => 0,
                    "_tax_status" => 'taxable',
                    "_tax_class" => '',
                    "_manage_stock" => 'no',
                    "_backorders" => 'no',
                    "_sold_individually" => 'yes',
                    "_weight" => '',
                    "_length" => '',
                    "_width" => '',
                    "_height" => '',
                    //  "_upsell_ids"=>'',
                    // "_crosssell_ids"=>'',
                    "_purchase_note" => '',
                    // "_default_attributes"=>'',
                    "_virtual" => 'no',
                    "_downloadable" => 'no',
                    "_product_image_gallery" => '',
                    "_download_expiry" => -1,
                    "_download_limit" => -1,
                    "_stock" => 0,
                    "_stock_status" => 'instock',
                    "_product_version" => '3.4.3',
                );
            $cat = explode('*',$this->Getcat($data['cat']));
            $post=
                [
                    "post_author"=>$this->user,
                    "post_title"=>$data['uniq'],
                    "post_status"=>'publish',
                    "post_type"=>'product',
                    "comment_status"=>'open',
                    "ping_status"=>'closed',
                    "tax_input"=>["product_cat"=>$cat,"product_type"=>[$this->texo['variable']]],
                    "meta_input"=>$meta,



                ];
        }

        $i= wp_insert_post($post);
        if($uniq==false)
        {
            $m='a:1:{s:27:"pa_%d8%b3%d8%a7%db%8c%d8%b2";a:6:{s:4:"name";s:11:"pa_سایز";s:5:"value";s:0:"";s:8:"position";i:0;s:10:"is_visible";i:0;s:12:"is_variation";i:1;s:11:"is_taxonomy";i:1;}}';
            global $wpdb;
            $wpdb->insert($wpdb->postmeta,["meta_value"=>$m,"meta_key"=>"_product_attributes","post_id"=>$i]);
            $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_upsell_ids","meta_value"=>"a:0:{}"]);
            $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_crosssell_ids","meta_value"=>"a:0:{}"]);
            $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_default_attributes","meta_value"=>"a:0:{}"]);
            $wpdb->insert($wpdb->postmeta,["post_id"=>$i,"meta_key"=>"_wc_rating_count","meta_value"=>"a:0:{}"]);

        }
        return $i;
    }

    protected function Timerun()
    {
        if($this->lasttime+(($this->time-2)*60) <= time())
            return true;
        return false;
    }

    protected function Gettoken($username,$password)
    {
        $json = file_get_contents("http://bazaraservices.mahaksoft.com/Sync/Login?username=$username&password=$password");
        $result = json_decode($json);
        if($result->result == "success")
            return $result->data->token;
        return "error";
    }
    protected function Getdata()
    {
        $data1=[];$data2=[];
      $token=$this->Gettoken($this->username,$this->password);
      $url = ("http://bazaraservices.mahaksoft.com/Sync/GetProducts?systemSyncID=$this->syncid&userToken=$token");
      $json=file_get_contents($url);
      $product=json_decode($json);
      $url = ("http://bazaraservices.mahaksoft.com/Sync/GetPrices?systemSyncID=$this->syncid&userToken=$token");
      $json=file_get_contents($url);
      $price=json_decode($json);
      $mahakdata=[];
      for($i=0,$cp=count($product->data);$i<count($price->data);$i++)
      {

          $prikey=$price->data[$i];
          $data2[$prikey->ProductID] = ["price"=>$prikey->Price1,"count"=>$prikey->AvailableCount];
          if($i>=$cp)
              continue;

      }
      
      for($i=0;$i<count($product->data);$i++)
      {
          $prokey=$product->data[$i];
          $name = explode("^",str_replace(" ","",$prokey->Name));
          $data1[$prokey->Code] = ["code"=>$prokey->Code,"id"=>$prokey->ProductID,"uniq"=>$name[1],"cat"=>$name[0],"size"=>$this->Getuniq(isset($name[2])?$name[2]:''),"delete"=>$prokey->Deleted];
      }
      foreach ($data1 as $item) {
          if($item["uniq"]==""||$item["delete"]==1)
              continue;
              
          if(!isset($data2[$item["id"]]))
          continue;
          if($item['size']!='')
          $mahakdata[$item["uniq"].' - '.$this->texo[$item['size']]['name']] = array_merge($item, $data2[$item["id"]]);
          else
              $mahakdata[$item["uniq"]] = array_merge($item, $data2[$item["id"]]);
      }

        global $wpdb;
        $post= $wpdb->get_results("SELECT * FROM $wpdb->posts where post_type LIKE 'product' OR post_type LIKE 'product_variation';");
        $meta= $wpdb->get_results("SELECT * FROM $wpdb->postmeta where meta_key LIKE '_price' OR meta_key LIKE '_stock';");
        $poval=[];
        $meval=[];

        for($i=0,$cp = count($post);$i<count($meta);$i++)
        {
            $d1 = $meta[$i];
            if(!isset($meval[$d1->post_id]))
            $meval[$d1->post_id]=[$d1->meta_key=>['id'=>$d1->meta_id,'value'=>$d1->meta_value]];
            else
            $meval[$d1->post_id]=array_merge($meval[$d1->post_id],[$d1->meta_key=>['id'=>$d1->meta_id,'value'=>$d1->meta_value]]);
            if($i>=$cp)
                continue;
            $d1 = $post[$i];
            $poval[$d1->ID]=['id'=>$d1->ID,'title'=>$d1->post_title,'name'=>$d1->post_name,'type'=>$d1->post_type,'guid'=>$d1->guid,"parent"=>$d1->post_parent];
        }
        $wocoadata=[];
        foreach ($poval as $key=> $value)
        {
            $name = $value['title'];
            if(isset($meval[$key]))
                $wocoadata[$name]= array_merge($value,$meval[$key]);
            else
            $wocoadata[$name]=$value;
        }
        $this->mahak=$mahakdata;
        $this->wocom=$wocoadata;


    }

    protected function Getuniq($data)
    {
        if(isset($this->abb->$data) || isset($this->abb[$data]) )
            return isset($this->abb->$data)?$this->abb->$data:$this->abb[$data];
        return $data;



    }

    protected function Getcat($name)
    {
        $cat=explode('*',$name);
        $name ='';
        foreach ($cat as $value) {
            if ($value=="")
                continue;
            if($name=='')
                $name = $this->Getuniq($value);
            else
            $name = $name . '*' . $this->Getuniq($value);
        }
        return $this->catgory[$name];
        return $name;
    }



    protected function Getterms()
    {
        $terms= get_terms(['orderby'=>'id','hide_empty'=>false]);
        $catgory=[];
        $termname=[];
        $termid = [];
        $m=[];
        foreach ($terms as $value)
        {
            if($value->taxonomy!="product_cat")
                continue;
            if($value->parent==0) {
                $termname[$value->term_id]=$value->name;
                $termid[$value->term_id]=$value->term_id;
            }
            else
            {
                $termname[$value->term_id]=$termname[$value->parent]."*$value->name";
                $termid[$value->term_id]=$termid[$value->parent]."*$value->term_id";
                $m[$value->parent]="yes";
            }

        }
        foreach ($termname as $key => $value )
        {
            if(isset($m[$key]))
                continue;
            $catgory["$value"] = $termid["$key"];
        }
        return $catgory;

    }
    protected function getattrib()
    {
        $terms= get_terms(['orderby'=>'id','hide_empty'=>false]);
        foreach ($terms as $value)
        {
            if($value->name=='variable'||$value->name=='simple')
                $this->texo[$value->name]=$value->term_id;
            if($value->taxonomy!="pa_سایز")
                continue;
            $this->texo[$value->name]=["id"=>$value->term_id,"name"=>$value->slug];
        }

    }

    public function Setform()
    {
        ?>
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-6">
                    <form role="form" name="form" action="#" method="post" enctype="multipart/form-data">
                        <div class="form-group">

                            <label for="Time">
                                زمان تکرار به دقیقه
                            </label>
                            <input  name="time" class="form-control" id="Time" type="number" value="<?php echo $this->time  ?>" />
                        </div>
                        <div class="form-group">

                            <label for="Username">
                                نام کاربری سیستم محک
                            </label>
                            <input name="username" class="form-control" id="Username" type="text" value="<?php echo $this->username  ?>" />
                        </div>
                        <div class="form-group">

                            <label for="Password">
                                کلمه عبور سیستم محک
                            </label>
                            <input name="password" class="form-control" id="Password" type="password" value="<?php echo $this->password  ?>" />
                        </div>
                        <div class="form-group">

                            <label for="syncid">
                                ID دیتابیس سیستم محک
                            </label>
                            <input name="syncid" class="form-control" id="syncid" type="number" value="<?php echo $this->syncid  ?>" />
                        </div>
                        <div class="form-group">

                            <label for="Password">
                                اطلاعات دیگر
                            </label>
                            <textarea name="attrib" class="form-control" id="attrib"> <?php echo json_encode($this->attrib)?></textarea>
                        </div>
                        <div class="form-group">

                            <label for="file">
                                آپلود فایل
                            </label>
                            <input class="form-control-file" id="file" type="file" name="data" />
                            <p class="help-block">
                                فایل csv مخفف شده خود را از این قسمت آپلود کنید
                            </p>
                        </div>
                        <button type="submit" class="btn btn-primary" name="submit">
                            به روز رسانی
                        </button>
                    </form>
                </div>
                <div class="col-md-6">
                    <h1>
                        توضیحات
                    </h1>
                    <p>
                    <h6>برای فعال سازی افزونه طلاعات خواسته شد را پر کنیدکه شامل</h6>
                        <br />
                        <ol>
                        <li>زمان تکرار حلقه</li>
                        <li>نام کاربری سیستم محک</li>
                        <li>پسورد سیستم محک</li>
                        <li>شماره دیتابیسی که اطلاعات شما قرار است از روی آن خوانده شود</li>
                        <li>اطلاعات که به صورت مخفف در سیستم محک ثبت شده و در سیستم فروشگاهی شما به اسم دیگری است به صورت فایل  csv کلمه مخفف شده بدون فاصله وارد شود</li>
                        </ol>
                    این افزونه به صورتی کار میکند که بعد از باز شدن سایت شما اگر از زمان به روز رسانی قبلی گذشته باشد شروع  به روز رسانی اطلاعات شما از سیستم محک میکند.
                    <br />
                    </p>
                </div>
            </div>
        </div>

        <?php
    }
}