if ($value == "") {
   switch ($entity->field_lit_code->value) {
    case 'P':
       $value = $entity->field_product_id->value . 'IIED';
    break;
    case 'X':
      $value = 'X' . $entity->field_product_id->value;
    break;
   case 'S':
      $value = 'G' . $entity->field_product_id->value;
    break;
  }
}
