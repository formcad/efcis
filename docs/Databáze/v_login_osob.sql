/* Pohled používaný při autentizaci a autorizaci uživatelů IS */

SELECT osoby.id_osoby AS id,
       osoby.jmeno,
       osoby.prijmeni,
       osoby.oznaceni,
       osoby.heslo,
       osoby.posledni_sdeleni_systemu as event,
       crosstab."default",
       crosstab."dochazka",
       crosstab."uzivatele"
       /* sem doplnit případný další modul */
       
FROM crosstab ('

	SELECT 
	  osoby.id_osoby, 
	  moduly.nazev, 
	  role.nazev
	FROM 
	  moduly, 
	  osoby, 
	  role, 
	  role_osob
	WHERE 
	  moduly.id_modulu = role_osob.id_modulu AND
	  osoby.id_osoby = role_osob.id_osoby AND
	  role.id_role = role_osob.id_role
	ORDER BY osoby.id_osoby,
		 moduly.id_modulu;

') as ( 
	"id_osoby" integer,
	"default" character varying,
	"dochazka" character varying,
	"uzivatele" character varying
	/* sem doplnit případný další modul */
)

JOIN osoby USING (id_osoby);